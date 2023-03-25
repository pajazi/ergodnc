<?php

use App\Http\Controllers\OfficeController;
use App\Http\Controllers\TagsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

//Tags...
Route::get('/tags', TagsController::class);

//Offices...
Route::get('/offices', [OfficeController::class, 'index']);
