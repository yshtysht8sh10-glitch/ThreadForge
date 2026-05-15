<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PostApiTest extends TestCase
{
    public function testCreateUpdateAndSoftDeletePost(): void
    {
        $this->postJson('/api/post', [
            'name' => 'Alice',
            'title' => 'Original title',
            'message' => 'Original body',
            'password' => 'secret',
        ])->assertOk()->assertJson(['success' => true]);

        $post = Post::firstOrFail();
        $this->assertSame($post->id, $post->thread_id);
        $this->assertNull($post->deleted_at);

        $this->postJson('/api/post/' . $post->id, [
            'name' => 'Alice Updated',
            'title' => 'Updated title',
            'message' => 'Updated body',
            'password' => 'secret',
        ])->assertOk()->assertJson(['success' => true]);

        $this->getJson('/api/post/' . $post->id)
            ->assertOk()
            ->assertJsonPath('name', 'Alice Updated')
            ->assertJsonPath('title', 'Updated title')
            ->assertJsonPath('message', 'Updated body');

        $this->deleteJson('/api/post/' . $post->id, [
            'password' => 'secret',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, Post::count());
        $this->assertNotNull(Post::firstOrFail()->deleted_at);
        $this->getJson('/api/post/' . $post->id)->assertNotFound();
    }

    public function testDeletingThreadSoftDeletesReplies(): void
    {
        $thread = $this->createPost([
            'name' => 'Thread author',
            'title' => 'Thread title',
            'message' => 'Thread body',
            'password' => 'secret',
        ]);
        $thread->thread_id = $thread->id;
        $thread->save();

        $reply = $this->createPost([
            'thread_id' => $thread->id,
            'parent_id' => $thread->id,
            'name' => 'Reply author',
            'title' => 'Re: Thread title',
            'message' => 'Reply body',
            'password' => 'reply-secret',
        ]);

        $this->deleteJson('/api/post/' . $thread->id, [
            'password' => 'secret',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(2, Post::count());
        $this->assertNotNull(Post::findOrFail($thread->id)->deleted_at);
        $this->assertNotNull(Post::findOrFail($reply->id)->deleted_at);
    }

    public function testImageUploadSuccessPath(): void
    {
        Storage::fake('local');

        $this->post('/api/post', [
            'name' => 'Image user',
            'title' => 'Image title',
            'message' => 'Image body',
            'password' => 'secret',
            'file' => UploadedFile::fake()->image('drawing.png'),
        ])->assertOk()->assertJson(['success' => true]);

        $post = Post::firstOrFail();
        $this->assertNotNull($post->image_path);
        Storage::disk('local')->assertExists($post->image_path);
    }

    public function testSearchHonorsEmptyQueryLimitPageScopeAndDeletedRows(): void
    {
        $this->createPost(['name' => 'Alice', 'title' => 'Needle title', 'message' => 'Body one']);
        $this->createPost(['name' => 'Bob', 'title' => 'Second title', 'message' => 'Needle body']);
        $this->createPost(['name' => 'Carol', 'title' => 'Deleted Needle', 'message' => 'Hidden', 'deleted_at' => now()]);

        $this->getJson('/api/search?q=')->assertOk()->assertExactJson([]);

        $this->getJson('/api/search?q=title&limit=1&page=2')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Needle title');

        $this->getJson('/api/search?q=Needle&scope=message')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.message', 'Needle body');
    }

    public function testPasswordMismatchAndMissingPostReturnErrors(): void
    {
        $post = $this->createPost([
            'name' => 'Alice',
            'title' => 'Title',
            'message' => 'Body',
            'password' => 'secret',
        ]);

        $this->postJson('/api/post/' . $post->id, [
            'name' => 'Alice',
            'title' => 'Changed',
            'message' => 'Changed',
            'password' => 'wrong',
        ])->assertForbidden()->assertJson(['success' => false]);

        $this->deleteJson('/api/post/' . $post->id, [
            'password' => 'wrong',
        ])->assertForbidden()->assertJson(['success' => false]);

        $this->deleteJson('/api/post/999999', [
            'password' => 'secret',
        ])->assertNotFound()->assertJson(['success' => false]);
    }

    private function createPost(array $attributes): Post
    {
        $post = Post::create([
            'thread_id' => (int)($attributes['thread_id'] ?? 0),
            'parent_id' => (int)($attributes['parent_id'] ?? 0),
            'name' => (string)($attributes['name'] ?? 'User'),
            'title' => (string)($attributes['title'] ?? 'Title'),
            'message' => (string)($attributes['message'] ?? 'Body'),
            'image_path' => $attributes['image_path'] ?? null,
            'password_hash' => password_hash((string)($attributes['password'] ?? 'secret'), PASSWORD_DEFAULT),
            'deleted_at' => $attributes['deleted_at'] ?? null,
        ]);

        if ($post->thread_id === 0) {
            $post->thread_id = $post->id;
            $post->save();
        }

        return $post;
    }
}
