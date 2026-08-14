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
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relacionamentos
            |--------------------------------------------------------------------------
            */

            $table->foreignId('product_category_id')
                ->constrained('product_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Identificação
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            $table->string('sku', 50)->unique();

            $table->string('barcode', 100)->nullable()->unique();

            /*
            |--------------------------------------------------------------------------
            | Unidade
            |--------------------------------------------------------------------------
            */

            $table->string('unit', 30)
                ->default('Unidade');

            /*
            |--------------------------------------------------------------------------
            | Stock
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('quantity')
                ->default(0);

            $table->unsignedInteger('minimum_quantity')
                ->default(0);

            $table->unsignedInteger('maximum_quantity')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Custos
            |--------------------------------------------------------------------------
            */

            $table->decimal('estimated_unit_cost', 15, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Localização
            |--------------------------------------------------------------------------
            */

            $table->string('location')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $table->boolean('active')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Observações
            |--------------------------------------------------------------------------
            */

            $table->text('description')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Datas
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index('name');

            $table->index('sku');

            $table->index('barcode');

            $table->index('product_category_id');

            $table->index('supplier_id');

            $table->index('quantity');

            $table->index('minimum_quantity');

            $table->index('active');

            $table->index('created_at');

            $table->timestamp('last_inventory_at')->nullable();

            $table->foreignId('last_inventory_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('products');

        Schema::enableForeignKeyConstraints();
    }
};
