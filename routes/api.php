<?php

use Illuminate\Support\Facades\Route;

//Controllers
use App\Http\Controllers\Api\V1\Chk\ChecklistController;
use App\Http\Controllers\Api\V1\Chk\AreaController;
use App\Http\Controllers\Api\V1\Chk\ItemController;
use App\Http\Controllers\Api\V1\Chk\FieldTypeConfigController;
use App\Http\Controllers\Api\V1\App\ApplicationAnswerController;
use App\Http\Controllers\Api\V1\App\AppAttachmentController;
use App\Http\Controllers\Api\V1\Sys\EmailGroupController;


//middleware
use App\Http\Middleware\VerifyExternalToken;

Route::group(['prefix' => 'v1'], function () {
    Route::view('/', 'welcome');

    Route::middleware([VerifyExternalToken::class])->group(function () {
        Route::get('/checklist-templates', [ChecklistController::class, 'index']);
        Route::get('/checklist-templates/{id}', [ChecklistController::class, 'show']);
        Route::post('/checklist-templates', [ChecklistController::class, 'create']);
        Route::put('/checklist-templates/{id}', [ChecklistController::class, 'update']);
        Route::delete('/checklist-templates/{id}', [ChecklistController::class, 'destroy']);

        // Areas do Checklist 
        Route::get('/checklist-templates/{templateId}/areas', [AreaController::class, 'index']);
        Route::post('/checklist-templates/{templateId}/areas', [AreaController::class, 'store']);
        Route::get('/checklist-areas/{id}', [AreaController::class, 'show']);
        Route::put('/checklist-areas/{id}', [AreaController::class, 'update']);
        Route::delete('/checklist-areas/{id}', [AreaController::class, 'destroy']);

        // Itens do Checklist
        Route::get('/checklist-areas/{areaId}/items', [ItemController::class, 'index']);
        Route::post('/checklist-areas/{areaId}/items', [ItemController::class, 'store']);
        Route::get('/checklist-items/{id}', [ItemController::class, 'show']);
        Route::put('/checklist-items/{id}', [ItemController::class, 'update']);
        Route::delete('/checklist-items/{id}', [ItemController::class, 'destroy']);

        // Tipos de Campo Routes
        Route::get('/field-types-config', [FieldTypeConfigController::class, 'index']);

        // Aplicar checklist
        Route::post('/checklist-applications', [ApplicationAnswerController::class, 'store']);
        Route::get('/checklist-applications', [ApplicationAnswerController::class, 'index']);
        Route::get('/checklist-applications/{applicationId}', [ApplicationAnswerController::class, 'show']);
        Route::put('/checklist-applications/{applicationId}/save', [ApplicationAnswerController::class, 'storePartial']);
        Route::put('/checklist-applications/{applicationId}/submit', [ApplicationAnswerController::class, 'storeAndSubmit']);
        Route::delete('/checklist-applications/{applicationId}', [ApplicationAnswerController::class, 'destroy']);

        // Anexo de Respostas
        Route::prefix('attachments')->group(function () {
            Route::get('/{type}/{referenceId}', [AppAttachmentController::class, 'index']); // type: option ou answer
            Route::post('/{type}/{referenceId}', [AppAttachmentController::class, 'store']);
            Route::get('/{id}', [AppAttachmentController::class, 'show']);
            Route::delete('/{id}', [AppAttachmentController::class, 'destroy']);
        });


        // Email Groups
        Route::prefix('email-groups')->group(function() {
            Route::get('/', [EmailGroupController::class, 'index']);
            Route::post('/', [EmailGroupController::class, 'store']);
            Route::get('/{id}', [EmailGroupController::class, 'show']);
            Route::put('/{id}', [EmailGroupController::class, 'update']);
            Route::delete('/{id}', [EmailGroupController::class, 'destroy']);
        });
    });
});




