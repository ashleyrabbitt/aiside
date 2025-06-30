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
        Schema::create('business_ideas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('description');
            $table->string('niche')->nullable();
            $table->string('target_audience')->nullable();
            $table->text('offer_details')->nullable();
            $table->json('funnel_data')->nullable();
            $table->enum('status', ['draft', 'active', 'launched', 'archived'])->default('draft');
            $table->integer('weekly_hours_required')->nullable();
            $table->string('revenue_potential')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_ideas');
    }
};