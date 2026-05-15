<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function listThreads()
    {
        $threads = Post::where('parent_id', 0)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        return response()->json($threads);
    }

    public function getThread(int $id)
    {
        $thread = Post::whereNull('deleted_at')->find($id);
        if (!$thread) {
            return response()->json(['success' => false, 'message' => 'スレッドが見つかりません。'], 404);
        }

        $replies = Post::where('thread_id', $id)
            ->where('id', '!=', $id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['thread' => $thread, 'replies' => $replies]);
    }

    public function getPost(int $id)
    {
        $post = Post::whereNull('deleted_at')->find($id);
        if (!$post) {
            return response()->json(['success' => false, 'message' => '投稿が見つかりません。'], 404);
        }

        return response()->json($post);
    }

    public function search(Request $request)
    {
        $q = trim($request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $limit = min(100, max(1, (int)$request->query('limit', 50)));
        $page = max(1, (int)$request->query('page', 1));
        $scope = $request->query('scope', 'all');
        $pattern = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';

        $posts = Post::whereNull('deleted_at')
            ->where(function ($query) use ($scope, $pattern) {
                if ($scope === 'title') {
                    $query->where('title', 'like', $pattern);
                } elseif ($scope === 'message') {
                    $query->where('message', 'like', $pattern);
                } elseif ($scope === 'name') {
                    $query->where('name', 'like', $pattern);
                } else {
                    $query->where('title', 'like', $pattern)
                        ->orWhere('message', 'like', $pattern)
                        ->orWhere('name', 'like', $pattern);
                }
            })
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json($posts);
    }

    public function createPost(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'password' => 'required|string',
            'thread_id' => 'nullable|integer',
            'parent_id' => 'nullable|integer',
            'file' => 'nullable|file|mimes:png,jpg,jpeg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('file')) {
            $imagePath = $request->file('file')->store('public/storage/data');
        }

        $post = Post::create([
            'thread_id' => intval($validated['thread_id'] ?? 0),
            'parent_id' => intval($validated['parent_id'] ?? 0),
            'name' => $validated['name'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'image_path' => $imagePath,
            'password_hash' => password_hash($validated['password'], PASSWORD_DEFAULT),
        ]);

        if ($post->thread_id === 0) {
            $post->thread_id = $post->id;
            $post->save();
        }

        return response()->json(['success' => true, 'message' => '投稿が完了しました。']);
    }

    public function updatePost(Request $request, int $id)
    {
        $post = Post::whereNull('deleted_at')->find($id);
        if (!$post) {
            return response()->json(['success' => false, 'message' => '投稿が見つかりません。'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'password' => 'required|string',
            'file' => 'nullable|file|mimes:png,jpg,jpeg,gif|max:2048',
        ]);

        if (!password_verify($validated['password'], $post->password_hash)) {
            return response()->json(['success' => false, 'message' => 'パスワードが一致しません。'], 403);
        }

        if ($request->hasFile('file')) {
            if ($post->image_path) {
                Storage::delete($post->image_path);
            }
            $post->image_path = $request->file('file')->store('public/storage/data');
        }

        $post->name = $validated['name'];
        $post->title = $validated['title'];
        $post->message = $validated['message'];
        $post->save();

        return response()->json(['success' => true, 'message' => '投稿を更新しました。']);
    }

    public function deletePost(Request $request, int $id)
    {
        $post = Post::whereNull('deleted_at')->find($id);
        if (!$post) {
            return response()->json(['success' => false, 'message' => '投稿が見つかりません。'], 404);
        }

        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (!password_verify($validated['password'], $post->password_hash)) {
            return response()->json(['success' => false, 'message' => 'パスワードが一致しません。'], 403);
        }

        $deletedAt = now();

        if ($post->thread_id === $post->id) {
            Post::where('thread_id', $post->id)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $deletedAt]);
        } else {
            $post->deleted_at = $deletedAt;
            $post->save();
        }

        return response()->json(['success' => true, 'message' => '投稿を削除しました。']);
    }
}
