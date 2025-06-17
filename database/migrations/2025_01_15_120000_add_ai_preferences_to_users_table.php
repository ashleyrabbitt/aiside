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
        Schema::table('users', function (Blueprint $table) {
            $table->string('expertise_level')->nullable()->after('email'); // beginner, intermediate, advanced, expert
            $table->string('industry')->nullable()->after('expertise_level'); // user's industry/domain
            $table->string('communication_style')->nullable()->after('industry'); // formal, casual, technical, creative
            $table->json('preferred_topics')->nullable()->after('communication_style'); // array of topics they're interested in
            $table->string('response_length_preference')->default('medium')->after('preferred_topics'); // short, medium, long, comprehensive
            $table->boolean('wants_examples')->default(true)->after('response_length_preference'); // prefer examples in responses
            $table->boolean('wants_step_by_step')->default(false)->after('wants_examples'); // prefer step-by-step explanations
            $table->json('ai_model_preferences')->nullable()->after('wants_step_by_step'); // preferred models for different tasks
            $table->string('default_ai_tone')->default('helpful')->after('ai_model_preferences'); // helpful, friendly, professional, casual, expert
            $table->integer('context_memory_length')->default(10)->after('default_ai_tone'); // how many previous messages to remember
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'expertise_level',
                'industry',
                'communication_style',
                'preferred_topics',
                'response_length_preference',
                'wants_examples',
                'wants_step_by_step',
                'ai_model_preferences',
                'default_ai_tone',
                'context_memory_length'
            ]);
        });
    }
};