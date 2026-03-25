<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compres_entrades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('usuari_id');
            $table->foreign('usuari_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('sessio_id')->constrained('sessions')->onDelete('cascade');
            $table->foreignId('seient_id')->constrained('seients')->onDelete('cascade');
            $table->decimal('preu_pagat', 10, 2);
            $table->timestamp('data_compra')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compres_entrades');
    }
};
