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
        Schema::create('wordpress_publish_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wordpress_connection_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_openai_id')->nullable()->constrained('user_openai')->onDelete('set null');
            $table->integer('wp_post_id')->nullable(); // WordPress post ID
            $table->string('title');
            $table->string('status'); // draft, published, scheduled, failed
            $table->string('post_type')->default('post'); // post, page, custom_post_type
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('permalink')->nullable();
            $table->json('categories')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable(); // Additional metadata about the publish
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wordpress_publish_history');
    }
};