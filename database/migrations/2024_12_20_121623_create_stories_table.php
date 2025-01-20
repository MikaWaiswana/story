<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id(); // Kolom id
            
            $table->unsignedBigInteger('user_id'); // Kolom user_id
            $table->unsignedBigInteger('category_id'); // Kolom category_id
            $table->string('title'); // Kolom title
            $table->text('content'); // Kolom content
            
            $table->timestamps(); // Kolom created_at dan updated_at

            // Mendefinisikan foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
