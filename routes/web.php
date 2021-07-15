<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\EquipmentController;

Route::get('/', function () {
    return view('welcome');
});

# Api Route
Route::post('/api/equipment/new', [EquipmentController::class, 'new']);
Route::put('/api/equipment/update', [EquipmentController::class, 'update']);
Route::get('/api/equipment/get', [EquipmentController::class, 'select']);
