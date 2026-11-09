<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\SlidingSessionToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\LocalController;
use App\Http\Controllers\Api\WilayahController;

Route::prefix('otp')->middleware('throttle:10,1')->group(function () {
    Route::post('request', [AuthController::class, 'otpRequest']);
    Route::post('verify', [AuthController::class, 'otpVerify']);
});

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('password/reset', [AuthController::class, 'passwordReset'])->middleware('throttle:5,1');
Route::post('agent/cek-nomor', [AgentController::class, 'cekNomor'])->middleware('throttle:30,1');
Route::post('agent/verifikasi', [AgentController::class, 'verifikasi'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum', SlidingSessionToken::class])->group(function () {
    Route::get('user', fn (Request $request) => $request->user());
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('agent/status', [AgentController::class, 'status']);
    Route::post('agent/submit', [AgentController::class, 'submit']);
    Route::post('agent/dokumen', [AgentController::class, 'uploadDocuments']);
    Route::post('agent/submit_app', [AgentController::class, 'submitApp']);
    Route::post('password/change', [AuthController::class, 'passwordChange']);
});

Route::prefix('wilayah')->middleware('throttle:60,1')->group(function () {
    Route::get('provinsi', [WilayahController::class, 'provinsi']);
    Route::get('kota', [WilayahController::class, 'kota']);
    Route::get('kecamatan', [WilayahController::class, 'kecamatan']);
    Route::get('kelurahan', [WilayahController::class, 'kelurahan']);
});

Route::prefix('dtdiri')->middleware('throttle:60,1')->group(function () {
    Route::get('pekerjaan', [LocalController::class, 'pekerjaan']);
    Route::get('subpekerjaan', [LocalController::class, 'subpekerjaan']);
    Route::get('bank', [LocalController::class, 'bank']);
});
