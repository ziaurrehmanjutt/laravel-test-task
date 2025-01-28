<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
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



Route::post('/tasks', [TaskController::class, 'store']); // Create a new task
Route::get('/tasks', [TaskController::class, 'index']); // List all tasks
Route::post('/tasks/{id}', [TaskController::class, 'updateStatus']); // Update task status