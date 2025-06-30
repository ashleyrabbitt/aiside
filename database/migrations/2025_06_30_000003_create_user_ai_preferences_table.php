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
        Schema::create('user_ai_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('weekly_hours_available')->default(5);
            $table->json('interests')->nullable();
            $table->json('skills')->nullable();
            $table->string('income_goal')->nullable();
            $table->text('business_experience')->nullable();
            $table->boolean('daily_reminders')->default(true);
            $table->time('reminder_time')->default('09:00:00');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ai_preferences');
    }
};