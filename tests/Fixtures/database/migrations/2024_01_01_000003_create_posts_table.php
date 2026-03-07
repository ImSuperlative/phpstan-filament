<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('slug')->nullable();
            $table->string('status');
            $table->boolean('is_featured')->default(false);
            $table->integer('views_count')->default(0);
            $table->float('rating')->nullable();
            $table->json('metadata')->nullable();
            $table->json('options')->nullable();
            $table->foreignUuid('author_id')->constrained();
            $table->foreignUuid('category_id')->nullable()->constrained();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }
};
