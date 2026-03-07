<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug');
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignUuid('post_id')->constrained();
            $table->foreignUuid('tag_id')->constrained();
            $table->primary(['post_id', 'tag_id']);
        });
    }
};
