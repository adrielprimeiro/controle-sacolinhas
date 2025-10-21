<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Só criar se a tabela não existir
        if (!Schema::hasTable('lives')) {
            Schema::create('lives', function (Blueprint $table) {
                $table->id();
                $table->date('data');
                $table->timestamps();
                $table->string('nome')->nullable();
                $table->text('descricao')->nullable();
                $table->decimal('preco', 8, 2)->nullable();
                $table->integer('quantidade')->default(0);
                $table->boolean('ativo')->default(true);
            });
        }

        // Adicionar campos que podem estar faltando
        if (!Schema::hasColumn('lives', 'tipo_live')) {
            Schema::table('lives', function (Blueprint $table) {
                $table->string('tipo_live', 50)->nullable()->after('data');
            });
        }

        if (!Schema::hasColumn('lives', 'plataformas')) {
            Schema::table('lives', function (Blueprint $table) {
                $table->text('plataformas')->nullable()->after('tipo_live');
            });
        }

        if (!Schema::hasColumn('lives', 'status')) {
            Schema::table('lives', function (Blueprint $table) {
                $table->string('status')->default('ativa')->after('plataformas');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('lives');
    }
};