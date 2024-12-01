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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['email', 'link']);
            $table->text('message')->nullable();
            $table->string('password', 255)->nullable();
            $table->string('sender_email', 255)->nullable();
            $table->string('recipient_email', 255)->nullable();
            $table->string('download_token', 255);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
