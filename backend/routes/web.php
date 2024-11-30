<?php

use App\Http\Controllers;
use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/background-jobs');

Route::group(['prefix' => 'background-jobs', 'as' => 'backgroundJobs.'], function () {
    Route::get('/', [JobController::class, 'index'])->name('index');
    Route::post('/kill/{id}', [JobController::class, 'kill'])->name('kill');
    Route::post('/delete/{id}', [JobController::class, 'delete'])->name('delete');
    Route::post('/add-tests-jobs', [JobController::class, 'addTestJobs'])->name('addTests');
});
