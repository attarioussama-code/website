<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', 'user');
Route::get('/download-month-paper', [\App\Http\Controllers\MonthPaperController::class, 'downloadMonthPaper']);
