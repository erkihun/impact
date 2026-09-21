<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\SearchSuggestionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/search/suggestions', SearchSuggestionController::class)
        ->middleware('throttle:search')
        ->name('search.suggestions');
});
