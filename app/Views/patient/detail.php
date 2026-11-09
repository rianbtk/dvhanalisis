<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Analisis Pasien - <?= esc($patient['patient_name']) ?></title>
</head>
<body>
    <h2>Hasil Analisis Dosimetri: <?= esc($patient['patient_name']) ?></h2>
    <p><strong>No. Rekam Medis:</strong> <?= esc($patient['medical_record_number']) ?></p>
    
    <table border="1" cellpadding="8" cellspacing="0">
        <tr><th>Parameter</th><th>Nilai</th></tr>
        <tr><td>Volume Total PTV</td><td><?= esc($patient['total_ptv_volume']) ?> cm³</td></tr>
        <tr><td>D2%</td><td><?= esc($patient['d2']) ?></td></tr>
        <tr><td>D98%</td><td><?= esc($patient['d98']) ?></td></tr>
        <tr><td>D50%</td><td><?= esc($patient['d50']) ?></td></tr>
        <tr><td>V95%</td><td><?= esc($patient['v95']) ?></td></tr>
        <tr><td><strong>Homogeneity Index (HI)</strong></td><td><strong><?= esc($patient['homogeneity_index']) ?></strong> (Mendekati 0)</td></tr>
        <tr><td><strong>Conformity Index (CI)</strong></td><td><strong><?= esc($patient['conformity_index']) ?></strong> (Mendekati 1)</td></tr>
    </table>

    <br>
    <h3>Grafik DVH Kumulatif</h3>
    <?php if(!empty($patient['dvh_image'])): ?>
        <img src="<?= base_url('assets/img/dvh/' . $patient['dvh_image']) ?>" alt="Grafik DVH" width="600">
    <?php endif; ?>

    <br><br>
    <a href="<?= site_url('patients/export/' . $patient['id']) ?>">📥 Download Laporan Excel</a> | 
    <a href="<?= site_url('patients') ?>">← Kembali ke Daftar Pasien</a>
</body>
</html>