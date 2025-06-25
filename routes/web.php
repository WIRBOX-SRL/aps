<?php

use Illuminate\Support\Facades\Route;
use App\Models\Car;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserDomainController;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', function () {
    return redirect('/admin');
});
// Route to get the hierarchical structure of cars in JSON format
Route::get('/api/cars/hierarchy', function () {
    return response()->json(Car::getHierarchicalStructure());
});

// Route to get the nicely formatted JSON
Route::get('/api/cars/hierarchy-json', function () {
    return response()->json(json_decode(Car::generateHierarchicalJson()), 200, [], JSON_PRETTY_PRINT);
});

Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);
});

Route::get('/api/domain/{domain}', [UserDomainController::class, 'show']);

// Announcement routes with IP access control
Route::prefix('api/announcements')->group(function () {
    Route::get('/{id}', [App\Http\Controllers\AnnouncementController::class, 'show']);
    Route::get('/{id}/check-access', [App\Http\Controllers\AnnouncementController::class, 'checkAccess']);
    Route::get('/{id}/stats', [App\Http\Controllers\AnnouncementController::class, 'stats']);
});
