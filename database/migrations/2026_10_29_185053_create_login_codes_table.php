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
        Schema::create('login_codes', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')
        ->unique()
        ->constrained()
        ->cascadeOnDelete();

    $table->string('code');
    $table->timestamp('expires_at');
    $table->unsignedTinyInteger('resend_attempts')->default(0);
    $table->timestamp('blocked_until')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_codes');
    }
};
