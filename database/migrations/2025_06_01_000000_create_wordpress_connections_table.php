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
        Schema::create('wordpress_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');  // Friendly name for this connection
            $table->string('site_url');
            $table->string('auth_type')->default('app_password'); // app_password, oauth, basic
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // Encrypted application password
            $table->text('client_id')->nullable();  // For OAuth
            $table->text('client_secret')->nullable();  // For OAuth
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('capabilities')->nullable(); // Store WP capabilities
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wordpress_connections');
    }
};