<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Server endpoints
Route::post('/trigger/{client_id}', [AgentApiController::class, 'triggerDownload']);

// Agent endpoints
Route::get('/agent/jobs', [AgentApiController::class, 'getJobs']);
Route::post('/agent/upload/{job_id}', [AgentApiController::class, 'uploadFile']);
