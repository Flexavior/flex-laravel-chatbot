<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatBotController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\RecaptchaController;

Route::get('/', function() {
    return view('chatbot', [
        'is_embedded' => request()->has('embedded'),
        'customer_id' => request()->query('customer_id'),
        'service_number' => request()->query('service_number')
    ]);
})->name('chat.home');

Route::middleware(['auth'])->group(function() {
    Route::get('/upload', [FileUploadController::class, 'showUploadForm'])->name('upload.form');
    Route::post('/upload', [FileUploadController::class, 'handleFileUpload'])->name('upload.process');
});

Route::post('/send', [ChatBotController::class, 'sendChat'])->name('chat.send');
Route::get('/conversations/{serviceNumber}', [ChatBotController::class, 'getConversationHistory'])
     ->name('conversations.history');

Route::post('/api/validate-recaptcha', [RecaptchaController::class, 'validateRecaptcha'])
     ->name('recaptcha.validate');

Route::middleware(['cors'])->group(function() {
    Route::get('/embed/chat', function() {
        return view('chatbot', [
            'is_embedded' => true,
            'customer_id' => request()->query('customer_id'),
            'service_number' => request()->query('service_number')
        ]);
    })->name('chat.embed');
    
    Route::get('/embed/chat/{customer_id}/{service_number}', function($customer_id, $service_number) {
        return view('chatbot', [
            'is_embedded' => true,
            'customer_id' => $customer_id,
            'service_number' => $service_number
        ]);
    })->name('chat.embed.with-ids');
});