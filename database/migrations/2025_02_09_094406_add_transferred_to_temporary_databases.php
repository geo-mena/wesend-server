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
        Schema::table('temporary_databases', function (Blueprint $table) {
            $table->boolean('transferred')->default(false);
            $table->timestamp('transferred_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temporary_databases', function (Blueprint $table) {
            $table->dropColumn(['transferred', 'transferred_at']);
        });
    }
};
