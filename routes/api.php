<?php

use App\Http\Controllers\Api\SalesManagerController;
use App\Http\Controllers\Api\SalesUserController;
use App\Http\Controllers\Api\LeadStatusUpdateController;
use App\Http\Controllers\Api\APIParticipantController;
use App\Http\Controllers\Api\CompanyListController;
use App\Http\Controllers\Api\UserListController;
use App\Http\Controllers\BrevoCampaignController; //For Email Blust Campaign

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

Route::get('/SalesManager', [SalesManagerController::class, 'getSalesManager']);
Route::get('/SalesGrouping', [SalesUserController::class, 'getSalesUser']);
Route::get('/LeadStatusUpdate', [LeadStatusUpdateController::class, 'index']);
Route::get('/CompanyList', [CompanyListController::class, 'getCompanyList']);
Route::get('/users', [UserListController::class, 'getUserList']);






Route::post('/attendee', [APIParticipantController::class, 'attendee']);
Route::get('/participant-images/{id}', [APIParticipantController::class, 'getImages']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/brevo-webhook', [BrevoCampaignController::class, 'handleBrevoWebhook']);

