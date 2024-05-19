<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

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

Route::redirect('/', "login");

Auth::routes();

Route::get('/home', [ChatController::class, 'index'])->name('home');
Route::get('/chat/{id_other_contact}', [ChatController::class, 'show'])->name('chat');
Route::get('/chat/messages/{id_other_contact}', [ChatController::class, 'messagesJson']);

Route::post('/contact/store', [ChatController::class, 'contactStore']);
Route::post('/chat/{id_other_contact}/store', [ChatController::class, 'messageStore']);
Route::post('/chat/{id_other_contact}/delete', [ChatController::class, 'contactRemove']);
Route::post('/invit/{id_sender}/store', [ChatController::class, 'invitStore']);
