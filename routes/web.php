<?php

use App\Http\Controllers\BillReportController;
use App\Http\Controllers\ElectricityRecordController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/records');

Route::resource('records', ElectricityRecordController::class)->except('show');

Route::get('/report', BillReportController::class)->name('report');
