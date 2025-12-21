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
        Schema::create('concert_provider_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prn_artist_id')->index();
            $table->foreignId('artist_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 50)->index();
            $table->string('provider_artist_id', 255)->nullable();
            $table->boolean('ok');
            $table->unsignedInteger('status_code')->nullable();
            $table->unsignedInteger('result_count')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('response_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('concert_provider_sync_logs');
    }
};
