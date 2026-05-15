<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

Route::get('/threads', [PostController::class, 'listThreads']);
Route::get('/thread/{id}', [PostController::class, 'getThread']);
Route::get('/post/{id}', [PostController::class, 'getPost']);
Route::get('/search', [PostController::class, 'search']);
Route::post('/post', [PostController::class, 'createPost']);
Route::post('/post/{id}', [PostController::class, 'updatePost']);
Route::delete('/post/{id}', [PostController::class, 'deletePost']);
