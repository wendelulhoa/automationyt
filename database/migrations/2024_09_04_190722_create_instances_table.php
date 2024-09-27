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
        Schema::create('instances', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique()->nullable()->comment('Token de autenticação');
            $table->string('session_id')->unique()->comment('ID da sessão'); 
            $table->boolean('webhook')->default(false)->comment('Webhook ativo');
            $table->boolean('connected')->default(false)->comment('Está conectado?');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instances');
    }
};
