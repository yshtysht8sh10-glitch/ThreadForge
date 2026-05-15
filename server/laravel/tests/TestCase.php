<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(HttpKernelContract::class)->bootstrap();

        Route::prefix('api')->middleware('api')->group(__DIR__ . '/../routes/api.php');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('posts');
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->integer('thread_id');
            $table->integer('parent_id');
            $table->string('name');
            $table->string('title');
            $table->text('message');
            $table->string('image_path')->nullable();
            $table->string('password_hash');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('deleted_at')->nullable();
        });
    }
}
