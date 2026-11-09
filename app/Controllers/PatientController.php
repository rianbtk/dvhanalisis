<?php

namespace App\Controllers;

use App\Models\PatientDvhModel;
use CodeIgniter\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PatientController extends Controller
{
    public function index()
    {
        $model = new PatientDvhModel();
        $data['patients'] = $model->findAll();
        return view('patient/index', $data);
    }

    public function create()
    {
        return view('patient/create');
    }

    public function store()
    {
        $request = $this->request;
        
        $medicalRecord = $request->getPost('medical_record_number');
        $patientName = $request->getPost('patient_name');
        $prescriptionDose = $request->getPost('prescription_dose');
        
        $doseFile = $request->getFile('rt_dose_file');
        $structFile = $request->getFile('rt_struct_file');
        
        $doseName = $doseFile->getRandomName();
        $structName = $structFile->getRandomName();
        
        $doseFile->move(WRITEPATH . 'uploads', $doseName);
        $structFile->move(WRITEPATH . 'uploads', $structName);
        
        $dosePath = WRITEPATH . 'uploads/' . $doseName;
        $structPath = WRITEPATH . 'uploads/' . $structName;
        
        $imgName = 'dvh_' . time() . '.png';
        $imgOutputPath = FCPATH . 'assets/img/dvh/' . $imgName;
        
        // Eksekusi Script Python
        $pythonScript = ROOTPATH . 'python_scripts/analyzer_bridge.py';
        $command = escapeshellcmd("python \"$pythonScript\" \"$dosePath\" \"$structPath\" \"$imgOutputPath\" \"$prescriptionDose\"");
        $output = shell_exec($command);
        $analysisResult = json_decode($output, true);
        
        if (isset($analysisResult['status']) && $analysisResult['status'] == 'success') {
            $model = new PatientDvhModel();
            $data = [
                'medical_record_number' => $medicalRecord,
                'patient_name'          => $patientName,
                'prescription_dose'     => $prescriptionDose,
                'total_ptv_volume'      => $analysisResult['total_ptv_volume'],
                'd2'                    => $analysisResult['d2'],
                'd98'                   => $analysisResult['d98'],
                'd50'                   => $analysisResult['d50'],
                'v95'                   => $analysisResult['v95'],
                'homogeneity_index'     => $analysisResult['hi'],
                'conformity_index'      => $analysisResult['ci'],
                'rt_dose_file'          => $doseName,
                'rt_struct_file'        => $structName,
                'dvh_image'             => $imgName
            ];
            $model->insert($data);
            return redirect()->to('/patients')->with('success', 'Analisis DICOM berhasil disimpan!');
        } else {
            return redirect()->back()->with('error', 'Gagal memproses DICOM: ' . ($analysisResult['message'] ?? 'Error tidak dikenal'));
        }
    }

    public function detail($id)
    {
        $model   = new PatientDvhModel();
        $patient = $model->find($id);
        if (!$patient) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Pasien tidak ditemukan');
        }
        $data['patient'] = $patient;
        return view('patient/detail', $data);
    }

    public function delete($id)
    {
        $model = new PatientDvhModel();
        $model->delete($id);
        return redirect()->to('/patients')->with('success', 'Data rekam medis berhasil dihapus.');
    }

    public function exportExcel($id)
    {
        $model = new PatientDvhModel();
        $patient = $model->find($id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'LAPORAN ANALISIS DOSIMETRI RADIOTERAPI');
        $sheet->setCellValue('A3', 'No. Rekam Medis:'); $sheet->setCellValue('B3', $patient['medical_record_number']);
        $sheet->setCellValue('A4', 'Nama Pasien:'); $sheet->setCellValue('B4', $patient['patient_name']);
        $sheet->setCellValue('A5', 'Dosis Preskripsi:'); $sheet->setCellValue('B5', $patient['prescription_dose'] . ' cGy');
        
        $sheet->setCellValue('A7', 'Parameter Dosimetri'); $sheet->setCellValue('B7', 'Nilai');
        $sheet->setCellValue('A8', 'Volume Total PTV'); $sheet->setCellValue('B8', $patient['total_ptv_volume'] . ' cm3');
        $sheet->setCellValue('A9', 'D2%'); $sheet->setCellValue('B9', $patient['d2']);
        $sheet->setCellValue('A10', 'D98%'); $sheet->setCellValue('B10', $patient['d98']);
        $sheet->setCellValue('A11', 'D50%'); $sheet->setCellValue('B11', $patient['d50']);
        $sheet->setCellValue('A12', 'V95%'); $sheet->setCellValue('B12', $patient['v95']);
        $sheet->setCellValue('A13', 'Homogeneity Index (HI)'); $sheet->setCellValue('B13', $patient['homogeneity_index']);
        $sheet->setCellValue('A14', 'Conformity Index (CI)'); $sheet->setCellValue('B14', $patient['conformity_index']);

        $writer = new Xlsx($spreadsheet);
        $filename = 'Analisis_Dosimetri_' . $patient['medical_record_number'] . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit();
    }
}
