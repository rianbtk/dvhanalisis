<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Data Pasien & DICOM</title>
</head>
<body>
    <h2>Form Analisis Dosimetri DICOM</h2>

    <?php if (session()->getFlashdata('error')): ?>
        <p style="color: red;"><?= esc(session()->getFlashdata('error')) ?></p>
    <?php endif; ?>

    <form action="<?= site_url('patients/store') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <label>No. Rekam Medis:</label><br>
        <input type="text" name="medical_record_number" value="<?= esc(old('medical_record_number')) ?>" required><br><br>

        <label>Nama Pasien:</label><br>
        <input type="text" name="patient_name" value="<?= esc(old('patient_name')) ?>" required><br><br>

        <label>Dosis Preskripsi (cGy, contoh: 5000):</label><br>
        <input type="number" step="any" name="prescription_dose" value="<?= esc(old('prescription_dose')) ?>" required><br><br>

        <label>File DICOM RT Dose (.dcm):</label><br>
        <input type="file" name="rt_dose_file" accept=".dcm" required><br><br>

        <label>File DICOM RT Struct (.dcm):</label><br>
        <input type="file" name="rt_struct_file" accept=".dcm" required><br><br>

        <button type="submit">Proses Analisis & Ekstrak</button>
    </form>

    <p><a href="<?= site_url('patients') ?>">&laquo; Kembali ke daftar pasien</a></p>
</body>
</html>
