<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\Chk\ChecklistController;
use App\Http\Controllers\Api\V1\Chk\AreaController;
use App\Http\Controllers\Api\V1\Chk\ItemController;
use App\Http\Controllers\Api\V1\Chk\FieldTypeConfigController;
use App\Http\Controllers\Api\V1\App\ApplicationAnswerController;
use App\Http\Controllers\Api\V1\App\AppAttachmentController;
use App\Http\Controllers\Api\V1\Sys\EmailGroupController;
use App\Http\Controllers\Api\V1\Sys\ClientController;
use App\Http\Controllers\Api\V1\Auth\AuthController;

use App\Http\Middleware\Authenticate;

Route::group(['prefix' => 'v1'], function () {
    Route::view('/', 'welcome');

    // Rotas públicas (sem autenticação)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    // Rotas protegidas (com autenticação)
    Route::middleware([Authenticate::class])->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Clients (gerenciamento)
        Route::get('/clients', [ClientController::class, 'index']);
        Route::post('/clients', [ClientController::class, 'store']);
        Route::get('/clients/{id}', [ClientController::class, 'show']);
        Route::put('/clients/{id}', [ClientController::class, 'update']);
        Route::delete('/clients/{id}', [ClientController::class, 'destroy']);

        // Checklists
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

        // Tipos de Campo
        Route::get('/field-types-config', [FieldTypeConfigController::class, 'index']);

        // Aplicar checklist
        Route::post('/checklist-applications', [ApplicationAnswerController::class, 'store']);
        Route::get('/checklist-applications', [ApplicationAnswerController::class, 'index']);
        Route::get('/checklist-applications/{applicationId}', [ApplicationAnswerController::class, 'show']);
        Route::put('/checklist-applications/{applicationId}/save', [ApplicationAnswerController::class, 'storePartial']);
        Route::put('/checklist-applications/{applicationId}/submit', [ApplicationAnswerController::class, 'storeAndSubmit']);
        Route::delete('/checklist-applications/{applicationId}', [ApplicationAnswerController::class, 'destroy']);

        // Anexos
        Route::prefix('attachments')->group(function () {
            Route::get('/{type}/{referenceId}', [AppAttachmentController::class, 'index']);
            Route::post('/{type}/{referenceId}', [AppAttachmentController::class, 'store']);
            Route::get('/{id}', [AppAttachmentController::class, 'show']);
            Route::delete('/{id}', [AppAttachmentController::class, 'destroy']);
        });

        // Email Groups
        Route::prefix('email-groups')->group(function () {
            Route::get('/', [EmailGroupController::class, 'index']);
            Route::post('/', [EmailGroupController::class, 'store']);
            Route::get('/{id}', [EmailGroupController::class, 'show']);
            Route::put('/{id}', [EmailGroupController::class, 'update']);
            Route::delete('/{id}', [EmailGroupController::class, 'destroy']);
        });
    });
});
