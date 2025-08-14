<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;
use App\Http\Controllers\ChatController;
use App\Models\Chat;

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

Route::view('/ai', 'ai')->name('ai.page');
Route::redirect('/', '/ai');
Route::post('/ai/chat', [ChatController::class, 'chat']);
Route::post('/ai/upload', [AIController::class, 'process'])->name('chat.send');
Route::get('/get-last-summary', [AIController::class, 'getLastSummary']);
Route::get('/chat-history/{id}', [ChatController::class, 'show']);
