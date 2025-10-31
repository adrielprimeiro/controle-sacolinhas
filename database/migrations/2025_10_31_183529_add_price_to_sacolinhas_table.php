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
        Schema::table('sacolinhas', function (Blueprint $table) {
            // Adiciona a coluna 'price' como decimal com 8 dígitos no total e 2 casas decimais
            // Pode ser 'nullable()' se o preço puder ser nulo, ou 'default(0.00)'
            $table->decimal('price', 8, 2)->nullable()->after('item_id'); // Ou depois de outra coluna relevante
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sacolinhas', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
