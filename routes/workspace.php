<?php

use App\Http\Controllers\Workspace\AttachmentController;
use App\Http\Controllers\Workspace\TaskCommentController;
use App\Http\Controllers\Workspace\TaskController;
use App\Http\Controllers\Workspace\TaskTemplateController;
use Illuminate\Support\Facades\Route;

/*
| Modul Workspace. Satu prefix bersama untuk admin, mentor, dan intern;
| pembatasan akses lewat Policy, bukan prefix route per role.
*/
Route::middleware('auth')->prefix('workspace')->name('workspace.')->group(function () {
    // Template tugas (onboarding dan assign massal)
    Route::get('templates', [TaskTemplateController::class, 'index'])->name('templates.index');
    Route::post('templates', [TaskTemplateController::class, 'store'])->name('templates.store');
    Route::put('templates/{template}', [TaskTemplateController::class, 'update'])->whereNumber('template')->name('templates.update');
    Route::delete('templates/{template}', [TaskTemplateController::class, 'destroy'])->whereNumber('template')->name('templates.destroy');

    // Tugas
    Route::get('{workspace}/tasks', [TaskController::class, 'index'])->whereNumber('workspace')->name('tasks.index');
    Route::post('{workspace}/tasks', [TaskController::class, 'store'])->whereNumber('workspace')->name('tasks.store');

    Route::prefix('tasks/{task}')->whereNumber('task')->group(function () {
        Route::get('/', [TaskController::class, 'show'])->name('tasks.show');
        Route::put('/', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::patch('status', [TaskController::class, 'updateStatus'])->name('tasks.status');
        Route::patch('review', [TaskController::class, 'review'])->name('tasks.review');
        Route::patch('checklist/{item}', [TaskController::class, 'toggleChecklistItem'])->whereNumber('item')->name('tasks.checklist');
        Route::post('template', [TaskTemplateController::class, 'storeFromTask'])->name('tasks.save-template');

        // Komentar dan lampiran: batas 10 permintaan unggah per menit per user
        Route::post('comments', [TaskCommentController::class, 'store'])->middleware('throttle:10,1')->name('tasks.comments.store');
        Route::post('attachments', [AttachmentController::class, 'store'])->middleware('throttle:10,1')->name('tasks.attachments.store');
    });

    // Unduh lampiran (storage private, dicek Policy)
    Route::get('attachments/{attachment}', [AttachmentController::class, 'download'])->whereNumber('attachment')->name('attachments.download');
});
