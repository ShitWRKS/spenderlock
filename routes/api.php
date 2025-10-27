<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CommunicationThreadController;
use App\Http\Controllers\Api\EmailImportController;
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

Route::middleware('passport')->group(function () {
    // Contracts endpoints
    Route::get('/contracts/upcoming', [ContractController::class, 'upcoming']);
    Route::get('/contracts/search', [ContractController::class, 'search']);
    Route::get('/contracts/{id}', [ContractController::class, 'show']);
    Route::post('/contracts/{id}/reminders', [ContractController::class, 'createReminder']);

    // Suppliers endpoints
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::get('/suppliers/{id}/contacts', [SupplierController::class, 'contacts']);

    // Contacts endpoints
    Route::get('/contacts/{id}', [ContactController::class, 'show']);
    Route::post('/contacts', [ContactController::class, 'store']);
    Route::put('/contacts/{id}', [ContactController::class, 'update']);
    Route::get('/contacts/search/email', [ContactController::class, 'searchByEmail']);

    // Comments endpoints
    Route::get('/comments/{type}/{id}', [CommentController::class, 'index']);
    Route::post('/comments/{type}/{id}', [CommentController::class, 'store']);

    // Communication Thread endpoints (NEW - CRM feature)
    Route::get('/communication-thread', [CommunicationThreadController::class, 'index']);
    Route::post('/communication-thread/{type}/{id}/comment', [CommunicationThreadController::class, 'addComment']);

    // Gmail Import API (protetto da OAuth/passport)
    Route::post('/comments/import-email', [EmailImportController::class, 'importEmail']);

    // User info (will return null for client_credentials)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
