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
        Schema::create('filesend', function (Blueprint $table) {
            $table->id();
            $table->string('path')->comment('Caminho do arquivo');
            $table->string('type')->comment('Tipo do arquivo');
            $table->string('hash')->comment('Hash do arquivo');
            $table->timestamp('forget_in')->comment('Tempo para esquecer o arquivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filesend');
    }
};
