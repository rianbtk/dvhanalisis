<?php

namespace App\Models;

use CodeIgniter\Model;

class PatientDvhModel extends Model
{
    protected $table = 'patients_dvh';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'medical_record_number', 'patient_name', 'prescription_dose',
        'total_ptv_volume', 'd2', 'd98', 'd50', 'v95',
        'homogeneity_index', 'conformity_index',
        'rt_dose_file', 'rt_struct_file', 'dvh_image'
    ];
}