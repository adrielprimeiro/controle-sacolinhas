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
        Schema::create('lives', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->timestamps();
            
            // Adicione outros campos conforme necessário
            // Baseado no contexto "sacolinhas":
            $table->string('nome')->nullable();
            $table->text('descricao')->nullable();
            $table->decimal('preco', 8, 2)->nullable();
            $table->integer('quantidade')->default(0);
            $table->boolean('ativo')->default(true);
            
            // Índices para melhor performance
            $table->index('data');
            $table->index('ativo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lives');
    }
};
