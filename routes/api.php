<?php

use App\Http\Controllers\ContactFinderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API V1 routes. Candidates should add their endpoints here.
|
*/

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json(['status' => 'ok']));

    Route::post('/contact-finder', [ContactFinderController::class, 'find']);
    Route::post('/contact-finder/process-dataset', [ContactFinderController::class, 'processDataset']);
});
