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
        Schema::create('artist_identifiers', function (Blueprint $table) {
            $table->bigInteger('artist_id')->index();
            $table->string('type')->index();
            $table->string('identifier')->index();
            $table->string('value');
            $table->unique(['identifier', 'value']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_identifiers');
    }
};
