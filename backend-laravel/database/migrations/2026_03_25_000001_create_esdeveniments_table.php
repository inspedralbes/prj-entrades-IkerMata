<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('esdeveniments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('titol');
            $table->text('descripcio');
            $table->string('imatge_url');
            $table->integer('durada_minuts');
            $table->enum('estat', ['actiu', 'inactiu'])->default('actiu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esdeveniments');
    }
};
