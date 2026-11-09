<?php

use App\Controllers\PatientController;

$routes->get('/', [PatientController::class, 'index']);
$routes->get('patients', [PatientController::class, 'index']);
$routes->get('patients/create', [PatientController::class, 'create']);
$routes->post('patients/store', [PatientController::class, 'store']);
$routes->get('patients/detail/(:num)', [PatientController::class, 'detail']); // Perhatikan: tidak perlu /$1
$routes->get('patients/delete/(:num)', [PatientController::class, 'delete']);
$routes->get('patients/export/(:num)', [PatientController::class, 'exportExcel']);
