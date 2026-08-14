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
        Schema::create('distribution_items', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relacionamentos
            |--------------------------------------------------------------------------
            */

            $table->foreignId('distribution_id')
                ->constrained('distributions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('request_item_id')
                ->nullable()
                ->constrained('request_items')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Quantidades
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('quantity_requested')
                ->default(0);

            $table->unsignedInteger('quantity_distributed')
                ->default(0);

            $table->unsignedInteger('quantity_received')
                ->default(0);

            $table->unsignedInteger('quantity_returned')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [

                'pendente',

                'em_transporte',

                'entregue',

                'recebido',

                'devolvido',

            ])->default('pendente');

            /*
            |--------------------------------------------------------------------------
            | Observações
            |--------------------------------------------------------------------------
            */

            $table->text('notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Datas
            |--------------------------------------------------------------------------
            */

            $table->timestamp('delivered_at')
                ->nullable();

            $table->timestamp('received_at')
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

            $table->index('distribution_id');

            $table->index('request_item_id');

            $table->index('product_id');

            $table->index('status');

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('distribution_items');

        Schema::enableForeignKeyConstraints();
    }
};
