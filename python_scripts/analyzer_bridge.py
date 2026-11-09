import sys
import json
import os
import numpy as np
import matplotlib
matplotlib.use('Agg')  # Mencegah error GUI di server
import matplotlib.pyplot as plt
from pydicom import dcmread
from dicompylercore import dicomparser, dvhcalc


def process_dicom(dose_path, struct_path, output_img_path, ptv_name="PTV", prescription_dose=5000.0):
    try:
        # dicompylercore menggunakan dicomparser, bukan dcmread langsung
        rtss = dicomparser.DicomParser(struct_path)
        rtdose = dicomparser.DicomParser(dose_path)

        structures = rtss.GetStructures()
        ptv_roi_number = None
        for roi_number, structure in structures.items():
            if ptv_name.lower() in structure['name'].lower():
                ptv_roi_number = roi_number
                break

        if ptv_roi_number is None:
            names = ", ".join(s['name'] for s in structures.values())
            return {"status": "error",
                    "message": f"Struktur '{ptv_name}' tidak ditemukan. Struktur tersedia: {names}"}

        # Hitung DVH (dosis dalam cGy karena RT Dose umumnya disimpan dengan
        # DoseGridScaling & DoseUnits = GY dengan factor 100 -> dicompylercore
        # mengembalikan dalam cGy secara default)
        calc_dvh = dvhcalc.get_dvh(struct_path, dose_path, ptv_roi_number)

        # Volume total PTV (cm3)
        total_vol_ptv = float(calc_dvh.volume)

        # Batas dosis (constraint): get_dvh mengembalikan dosis dalam Gy.
        d2 = float(calc_dvh.dose_constraint(2).value)
        d98 = float(calc_dvh.dose_constraint(98).value)
        d50 = float(calc_dvh.dose_constraint(50).value)

        # V95 = volume (%) yang menerima >= 95% dosis preskripsi
        rx_gy = float(prescription_dose)  # input dalam cGy
        # dicompylercore: get_dvh mengembalikan DVH dengan dosis absolut (Gy)
        # dan volume absolut (cc). Gunakan volume_constraint pada dosis Gy.
        v95_obj = calc_dvh.volume_constraint(rx_gy * 0.95, 'Gy')
        v95_abs = float(v95_obj.value) if v95_obj is not None else 0.0
        v95 = (v95_abs / total_vol_ptv * 100.0) if total_vol_ptv > 0 else 0.0
        # Interpolasi lebih halus dari kurva kumulatif relatif (%)
        import numpy as _np
        _bins = _np.asarray(calc_dvh.bins, dtype=float)          # Gy (n+1 elemen)
        _rel = _np.asarray(calc_dvh.relative_volume.counts, dtype=float)  # % (n elemen)
        # pastikan sumbu x sepanjang data y (bins ke-n = batas atas bin terakhir)
        if _bins.size == _rel.size + 1:
            _bins = _bins[:-1]  # gunakan batas bawah tiap bin
        _target = rx_gy * 0.95 / 100.0  # Gy
        if _bins.size > 1:
            v95 = float(_np.interp(_target, _bins, _rel))
        else:
            v95 = 0.0

        # Rumus HI (ICRU 83) dan CI (akar konformitas sederhana)
        hi = (d2 - d98) / d50 if d50 > 0 else 0
        ci = (v95 / 100.0)  # proporsi 0-1

        # Grafik DVH Kumulatif (manual dengan matplotlib, tanpa plot bawaan DVH)
        dose_bins_gy = np.asarray(calc_dvh.bins, dtype=float)
        rel_vol = np.asarray(calc_dvh.relative_volume.counts, dtype=float)
        # rel_vol sudah dalam % (dicompylercore menormalkan ke max counts)
        if rel_vol.size == dose_bins_gy.size:
            dose_bins_gy = dose_bins_gy[:rel_vol.size]
        elif rel_vol.size < dose_bins_gy.size:
            dose_bins_gy = dose_bins_gy[:rel_vol.size]
        else:
            rel_vol = rel_vol[:dose_bins_gy.size]
        plt.figure(figsize=(8, 6))
        plt.step(dose_bins_gy, rel_vol, where='post',
                 label='PTV', color='purple', linewidth=2)
        # Garis vertikal: dosis preskripsi & 95% dosis
        plt.axvline(rx_gy / 100.0, color='red', linestyle='--', linewidth=1.2,
                    label=f'Rx {rx_gy/100:.1f} Gy')
        plt.axvline(rx_gy * 0.95 / 100.0, color='orange', linestyle=':', linewidth=1.2,
                    label='95% Rx')
        plt.title('DVH Kumulatif - PTV')
        plt.xlabel('Dosis (Gy)')
        plt.ylabel('Volume (%)')
        plt.xlim(0, max(dose_bins_gy.max(), rx_gy / 100.0) * 1.05)
        plt.ylim(0, 105)
        plt.grid(True, linestyle='--', alpha=0.6)
        plt.legend()
        plt.savefig(output_img_path, bbox_inches='tight', dpi=150)
        plt.close()

        return {
            "status": "success",
            "total_ptv_volume": round(total_vol_ptv, 2),
            "d2": round(d2, 2),
            "d98": round(d98, 2),
            "d50": round(d50, 2),
            "v95": round(v95, 2),
            "hi": round(float(hi), 4),
            "ci": round(float(ci), 4)
        }
    except Exception as e:
        return {"status": "error", "message": str(e)}


if __name__ == "__main__":
    if len(sys.argv) < 5:
        print(json.dumps({"status": "error", "message": "Argumen kurang."}))
        sys.exit(0)  # tetap keluar 0 agar JSON tetap terbaca oleh PHP

    d_path = sys.argv[1]
    s_path = sys.argv[2]
    img_path = sys.argv[3]
    pres_dose = float(sys.argv[4])

    # Pastikan folder output gambar ada
    os.makedirs(os.path.dirname(img_path), exist_ok=True)

    res = process_dicom(d_path, s_path, img_path, prescription_dose=pres_dose)
    # Tulis JSON ke stdout bersih (abaikan warning/notice dari library)
    sys.stderr.write('')  # stderr tidak dibaca PHP, aman untuk warning
    print(json.dumps(res))
