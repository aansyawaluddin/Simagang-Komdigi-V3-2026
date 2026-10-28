<?php

use App\Http\Controllers\Workspace\AttachmentController;
use App\Http\Controllers\Workspace\ChannelController;
use App\Http\Controllers\Workspace\DashboardController;
use App\Http\Controllers\Workspace\LabelController;
use App\Http\Controllers\Workspace\MessageController;
use App\Http\Controllers\Workspace\NotificationController;
use App\Http\Controllers\Workspace\TaskCommentController;
use App\Http\Controllers\Workspace\TaskController;
use App\Http\Controllers\Workspace\TaskTemplateController;
use App\Http\Controllers\Workspace\WorkspacePageController;
use Illuminate\Support\Facades\Route;

/*
| Modul Workspace. Satu prefix bersama untuk admin, mentor, dan intern;
| pembatasan akses lewat Policy, bukan prefix route per role.
*/
Route::middleware('auth')->prefix('workspace')->name('workspace.')->group(function () {
    // Halaman: daftar kelompok dan detail kelompok
    Route::get('/', [WorkspacePageController::class, 'index'])->name('index');
    Route::get('{workspace}', [WorkspacePageController::class, 'show'])->whereNumber('workspace')->name('show');

    // Ringkasan untuk dashboard (intern / mentor / admin)
    Route::get('dashboard', [DashboardController::class, 'summary'])->name('dashboard');

    // Template tugas (onboarding dan assign massal)
    Route::get('templates', [TaskTemplateController::class, 'index'])->name('templates.index');
    Route::post('templates', [TaskTemplateController::class, 'store'])->name('templates.store');
    Route::put('templates/{template}', [TaskTemplateController::class, 'update'])->whereNumber('template')->name('templates.update');
    Route::delete('templates/{template}', [TaskTemplateController::class, 'destroy'])->whereNumber('template')->name('templates.destroy');

    // Tugas milik intern yang login (dropdown logbook); didaftarkan sebelum tasks/{task}
    Route::get('tasks/mine', [TaskController::class, 'mine'])->name('tasks.mine');

    // Unggah lampiran lebih dulu, ditautkan lewat attachment_ids
    Route::post('attachments', [AttachmentController::class, 'upload'])->name('attachments.upload');

    // Aktivitas workspace berhalaman
    Route::get('{workspace}/activities', [WorkspacePageController::class, 'activities'])->whereNumber('workspace')->name('activities.index');

    // Label per tim
    Route::post('teams/{team}/labels', [LabelController::class, 'store'])->whereNumber('team')->name('labels.store');
    Route::patch('labels/{label}', [LabelController::class, 'update'])->whereNumber('label')->name('labels.update');
    Route::delete('labels/{label}', [LabelController::class, 'destroy'])->whereNumber('label')->name('labels.destroy');

    // Tugas
    Route::get('{workspace}/tasks', [TaskController::class, 'index'])->whereNumber('workspace')->name('tasks.index');
    Route::post('{workspace}/tasks', [TaskController::class, 'store'])->whereNumber('workspace')->name('tasks.store');

    Route::prefix('tasks/{task}')->whereNumber('task')->group(function () {
        Route::get('/', [WorkspacePageController::class, 'task'])->name('tasks.show');
        Route::put('/', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::patch('status', [TaskController::class, 'updateStatus'])->name('tasks.status');
        Route::patch('review', [TaskController::class, 'review'])->name('tasks.review');
        Route::patch('checklist/{item}', [TaskController::class, 'toggleChecklistItem'])->whereNumber('item')->name('tasks.checklist');
        Route::post('template', [TaskTemplateController::class, 'storeFromTask'])->name('tasks.save-template');

        // Komentar dan lampiran (batas unggah 10 file per menit dicek di AttachmentService)
        Route::post('comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
        Route::post('attachments', [AttachmentController::class, 'store'])->name('tasks.attachments.store');
    });

    // Channel dan pesan
    Route::get('channels', [ChannelController::class, 'index'])->name('channels.index');
    Route::post('{workspace}/channels', [ChannelController::class, 'store'])->whereNumber('workspace')->name('channels.store');

    Route::prefix('channels/{channel}')->whereNumber('channel')->group(function () {
        Route::get('messages', [MessageController::class, 'index'])->name('channels.messages.index');
        Route::post('messages', [MessageController::class, 'store'])->middleware('throttle:workspace-messages')->name('channels.messages.store');
        Route::post('read', [ChannelController::class, 'markRead'])->name('channels.read');
        Route::patch('/', [ChannelController::class, 'update'])->name('channels.update');
        Route::patch('mute', [ChannelController::class, 'mute'])->name('channels.mute');
        Route::patch('archive', [ChannelController::class, 'archive'])->name('channels.archive');
    });

    Route::prefix('messages/{message}')->whereNumber('message')->group(function () {
        Route::patch('/', [MessageController::class, 'update'])->middleware('throttle:workspace-messages')->name('messages.update');
        Route::delete('/', [MessageController::class, 'destroy'])->name('messages.destroy');
        Route::patch('pin', [MessageController::class, 'pin'])->name('messages.pin');
        Route::get('replies', [MessageController::class, 'replies'])->name('messages.replies');
        Route::post('reactions', [MessageController::class, 'react'])->name('messages.react');
    });

    // Unduh lampiran (storage private, dicek Policy)
    Route::get('attachments/{attachment}', [AttachmentController::class, 'download'])->whereNumber('attachment')->name('attachments.download');
});

// Lonceng notifikasi in-app (semua role yang login)
Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
    Route::post('{id}/read', [NotificationController::class, 'markRead'])->name('read');
});
