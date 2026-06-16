<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ChannelMemberController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePictureController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware('throttle:auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
    });

    Route::middleware('auth:sanctum')->group(function () {

        // authentication
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::get('conversations/my', [ConversationController::class, 'my'])->name('conversations.my');
        Route::get('conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('conversations/{conversation}/hide', [ConversationController::class, 'hide'])->name('conversations.hide');

        Route::get('p/{profile:handle}', [ProfileController::class, 'show'])->where(['profile' => '[a-z0-9_]+'])->name('profile.show');
        Route::put('p/{profile}', [ProfileController::class, 'update'])->where(['profile' => '[0-9_]+'])->name('profile.update');

        Route::post('p/{profile:handle}/pictures', [ProfilePictureController::class, 'store'])->where(['profile' => '[a-z0-9_]+'])->name('profile.picture.store');
        Route::delete('p/pictures/{profile_picture:uuid}', [ProfilePictureController::class, 'destroy'])->name('profile.picture.destroy');

        Route::get('p/{profile:handle}/messages', [MessageController::class, 'index'])->where(['profile' => '[a-z0-9_]+'])->name('profile.messages.index');

        Route::put('p/{profile:handle}/search', [MessageController::class, 'search'])->where(['profile' => '[a-z0-9_]+'])->name('profile.messages.search');
        Route::get('search/', [MessageController::class, 'index'])->where(['profile' => '[a-z0-9_]+'])->name('search');

        Route::resource('channel', ChannelController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']); // channel's main crud actions

        Route::get('channel/{channel}/members', [ChannelMemberController::class, 'index'])->name('channel_member.index');

        Route::post('channel/{channel}/join', [ChannelMemberController::class, 'join'])->name('channel_member.join');
        Route::post('channel/{channel}/invite', [ChannelMemberController::class, 'invite'])->name('channel_member.invite');
        Route::post('channel/{channel}/kick', [ChannelMemberController::class, 'kick'])->name('channel_member.kick');
        Route::delete('channel/{channel}/leave', [ChannelMemberController::class, 'leave'])->name('channel_member.leave');

        Route::post('channel/{channel}/block', [ChannelMemberController::class, 'block'])->name('channel_member.block');

        Route::resource('message', MessageController::class)
            ->only(['store', 'update']);
        Route::delete('message/{message}', [MessageController::class, 'hide'])->name('message.hide'); // hide for requester
        // (soft) delete if is sender and receiver hasn't seen
        Route::delete('message/{message}/revoke', [MessageController::class, 'destroy'])->name('message.destroy');
    });
});
