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
        Schema::create('context_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('context_id');
            $table->text('notes');
            $table->text('ai_summary')->nullable();
            $table->enum('ai_confidence', ['high', 'medium', 'low'])->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();
            
            $table->foreign('context_id')->references('id')->on('contexts')->onDelete('cascade');
            $table->index('context_id');
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('context_entries');
    }
};