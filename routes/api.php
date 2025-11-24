<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\ServiceRequestController;
use App\Http\Controllers\API\ImportController;
use App\Http\Controllers\API\ExportController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin,staff'])->group(function () {

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('students', StudentController::class);
    Route::apiResource('service-requests', ServiceRequestController::class);

    // This is the correct and only definition needed for the upload endpoint:
    Route::post('/service-requests/import', [ImportController::class, 'upload']);

    // Optional logs route (make sure you have a 'logs' method in ImportController)
    Route::get('/imports/logs', [ImportController::class, 'logs']);
    
    // The summary endpoint the frontend polls:
    Route::post('/service-requests/import', [ImportController::class, 'upload']);
    Route::get('/service-requests/import/summary', [ImportController::class, 'summary']);
});
