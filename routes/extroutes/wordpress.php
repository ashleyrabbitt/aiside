<?php

use App\Http\Controllers\Integration\WordPressConnectionController;
use App\Http\Controllers\Integration\WordPressPublishController;
use App\Http\Controllers\Integration\WordPressViewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WordPress Integration Routes
|--------------------------------------------------------------------------
|
| Routes for managing WordPress connections, publishing content, etc.
|
*/

Route::middleware(['auth', 'updateUserActivity'])
    ->prefix('dashboard/user/wordpress')
    ->name('dashboard.user.wordpress.')
    ->group(function () {
        // WordPress View Routes
        Route::get('/connections', [WordPressViewController::class, 'connections'])->name('connections');
        Route::get('/publish/{contentId}', [WordPressViewController::class, 'publish'])->name('publish');
        Route::get('/history', [WordPressViewController::class, 'history'])->name('history');
        
        // WordPress Connection API Routes
        Route::get('/connections', [WordPressConnectionController::class, 'index'])->name('connections.index');
        Route::post('/connections', [WordPressConnectionController::class, 'store'])->name('connections.store');
        Route::get('/connections/{connection}', [WordPressConnectionController::class, 'show'])->name('connections.show');
        Route::put('/connections/{connection}', [WordPressConnectionController::class, 'update'])->name('connections.update');
        Route::delete('/connections/{connection}', [WordPressConnectionController::class, 'destroy'])->name('connections.destroy');
        Route::post('/connections/{connection}/test', [WordPressConnectionController::class, 'test'])->name('connections.test');
        
        // WordPress Taxonomy Routes
        Route::get('/connections/{connection}/categories', [WordPressConnectionController::class, 'getCategories'])->name('connections.categories');
        Route::get('/connections/{connection}/tags', [WordPressConnectionController::class, 'getTags'])->name('connections.tags');
        Route::get('/connections/{connection}/post-types', [WordPressConnectionController::class, 'getPostTypes'])->name('connections.post-types');
        
        // WordPress Publishing Routes
        Route::get('/publish/history', [WordPressPublishController::class, 'history'])->name('publish.history');
        Route::post('/publish/draft', [WordPressPublishController::class, 'createDraft'])->name('publish.draft');
        Route::post('/publish/update-draft', [WordPressPublishController::class, 'updateDraft'])->name('publish.update-draft');
        Route::post('/publish/publish-draft', [WordPressPublishController::class, 'publishDraft'])->name('publish.publish-draft');
        Route::post('/publish/schedule', [WordPressPublishController::class, 'schedulePost'])->name('publish.schedule');
        Route::post('/publish/direct', [WordPressPublishController::class, 'directPublish'])->name('publish.direct');
        Route::post('/publish/preview', [WordPressPublishController::class, 'getPreview'])->name('publish.preview');
    });