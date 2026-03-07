<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained();
            $table->foreignUuid('author_id')->constrained();
            $table->nullableMorphs('commentable');
            $table->text('body');
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }
};
