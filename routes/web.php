<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


Route::middleware('auth')->group(function () {
    Route::get('/map', [\App\Http\Controllers\PinController::class, 'index'])->name('map');
    Route::get('/pins', [\App\Http\Controllers\PinController::class, 'list'])->name('pins.list');
    Route::post('/pins', [\App\Http\Controllers\PinController::class, 'store'])->name('pins.store');
});


Route::middleware('auth')->group(function () {
    Route::get('/pins/{pin}/comments', [\App\Http\Controllers\CommentController::class, 'index']);
    Route::post('/pins/{pin}/comments', [\App\Http\Controllers\CommentController::class, 'store']);
    Route::delete('/comments/{comment}', [\App\Http\Controllers\CommentController::class, 'destroy']);
    Route::post('/pins/{pin}/echo', [\App\Http\Controllers\ReactionController::class, 'toggle']);
});

Route::middleware('auth')->group(function () {
    Route::get('/friends', [\App\Http\Controllers\FriendController::class, 'index'])->name('friends.index');
    Route::post('/friends', [\App\Http\Controllers\FriendController::class, 'store'])->name('friends.store');
    Route::post('/friends/{friendship}/accept', [\App\Http\Controllers\FriendController::class, 'accept'])->name('friends.accept');
    Route::delete('/friends/{friendship}/decline', [\App\Http\Controllers\FriendController::class, 'decline'])->name('friends.decline');
    Route::delete('/friends/{user}', [\App\Http\Controllers\FriendController::class, 'destroy'])->name('friends.destroy');
});

Route::middleware('auth')->get('/pins/date-range', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $friendIds = $user->friendIds();

    $base = \App\Models\Pin::query()
        ->where('is_hidden', false)
        ->where(function ($q) use ($user, $friendIds) {
            $q->where('visibility', 'public')
              ->orWhere('user_id', $user->id)
              ->orWhere(function ($q2) use ($friendIds) {
                  $q2->where('visibility', 'friends')->whereIn('user_id', $friendIds);
              });
        });

    $min = (clone $base)->min(\DB::raw('COALESCE(event_date, DATE(created_at))'));
    $max = (clone $base)->max(\DB::raw('COALESCE(event_date, DATE(created_at))'));

    return response()->json([
        'min' => $min ? substr($min, 0, 10) : null,
        'max' => $max ? substr($max, 0, 10) : null,
    ]);
});

Route::middleware('auth')->get('/stories', [\App\Http\Controllers\StoryController::class, 'list'])->name('stories.list');
Route::middleware('auth')->group(function () {
    Route::post('/pins/{pin}/report',       [\App\Http\Controllers\ReportController::class, 'reportPin'])->name('pins.report');
    Route::post('/comments/{comment}/report',[\App\Http\Controllers\ReportController::class, 'reportComment'])->name('comments.report');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get   ('/reports',                [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    Route::post  ('/reports/{report}/dismiss',[\App\Http\Controllers\Admin\ReportController::class, 'dismiss'])->name('reports.dismiss');
    Route::post  ('/reports/{report}/hide',   [\App\Http\Controllers\Admin\ReportController::class, 'hide'])->name('reports.hide');
    Route::post  ('/reports/{report}/restore',[\App\Http\Controllers\Admin\ReportController::class, 'restore'])->name('reports.restore');
});