<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Analisis Dosimetri Radioterapi</title>
    <!-- Anda bisa menambahkan CDN Bootstrap di sini agar tampilan tabel lebih rapi -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dashboard Rekam Medis & Analisis Dosimetri</h2>
        <a href="<?= site_url('patients/create') ?>" class="btn btn-primary">+ Tambah Pasien & Analisis DICOM</a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>No. Rekam Medis</th>
                <th>Nama Pasien</th>
                <th>Preskripsi (cGy)</th>
                <th>Total PTV (cm³)</th>
                <th>HI</th>
                <th>CI</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($patients) && is_array($patients)): ?>
                <?php $i = 1; foreach ($patients as $p): ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= esc($p['medical_record_number']) ?></td>
                    <td><?= esc($p['patient_name']) ?></td>
                    <td><?= esc($p['prescription_dose']) ?></td>
                    <td><?= esc($p['total_ptv_volume']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= esc($p['homogeneity_index']) ?></span></td>
                    <td><span class="badge bg-success"><?= esc($p['conformity_index']) ?></span></td>
                    <td>
                        <a href="<?= site_url('patients/detail/' . $p['id']) ?>" class="btn btn-sm btn-info">Detail & Grafik</a>
                        <a href="<?= site_url('patients/export/' . $p['id']) ?>" class="btn btn-sm btn-success">Excel</a>
                        <a href="<?= site_url('patients/delete/' . $p['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">Belum ada data rekam medis pasien.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>