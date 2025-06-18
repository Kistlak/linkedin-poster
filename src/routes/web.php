<?php

use Illuminate\Support\Facades\Route;
use Kistlak\LinkedinPoster\Http\Controllers\LinkedInPostController;

Route::get('/linkedin/redirect/{id}', [LinkedInPostController::class, 'redirectToLinkedIn'])->name('linkedin.redirect');
Route::get('/linkedin/callback', [LinkedInPostController::class, 'handleLinkedInCallback'])->name('linkedin.callback');
Route::post('/linkedin/share/{model}/{id}', [LinkedInPostController::class, 'shareToLinkedIn'])->name('linkedin.share');
Route::get('/linkedin-share/{model}/{id}', [LinkedInPostController::class, 'linkedinShareIndex'])->name('linkedin.share.index.view');
Route::get('/linkedin-share-success', [LinkedInPostController::class, 'linkedinShareSuccessIndex'])->name('linkedin.share.success.index');
