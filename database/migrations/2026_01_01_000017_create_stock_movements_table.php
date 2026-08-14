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
        Schema::create('stock_movements', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relacionamentos
            |--------------------------------------------------------------------------
            */

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Origem da Movimentação
            |--------------------------------------------------------------------------
            */

            $table->foreignId('distribution_id')
                ->nullable()
                ->constrained('distributions')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('internal_request_id')
                ->nullable()
                ->constrained('internal_requests')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Tipo da Movimentação
            |--------------------------------------------------------------------------
            */

            $table->enum('type', [

                'entrada',

                'saida',

                'ajuste',

                'inventario',

                'distribuicao',

                'devolucao',

                'reposicao',

                'transferencia',

                'perda',

            ]);

            /*
            |--------------------------------------------------------------------------
            | Quantidades
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('quantity');

            $table->unsignedInteger('balance_before');

            $table->unsignedInteger('balance_after');

            /*
            |--------------------------------------------------------------------------
            | Referência
            |--------------------------------------------------------------------------
            */

            $table->string('reference', 100)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Observações
            |--------------------------------------------------------------------------
            */

            $table->text('notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Auditoria
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index('product_id');

            $table->index('user_id');

            $table->index('distribution_id');

            $table->index('internal_request_id');

            $table->index('type');

            $table->index('reference');

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('stock_movements');

        Schema::enableForeignKeyConstraints();
    }
};
