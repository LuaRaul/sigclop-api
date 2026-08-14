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
        Schema::create('distributions', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identificação
            |--------------------------------------------------------------------------
            */

            $table->string('distribution_number', 50)
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Relacionamentos
            |--------------------------------------------------------------------------
            */

            $table->foreignId('internal_request_id')
                ->nullable()
                ->constrained('internal_requests')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('delivered_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Fluxo da Distribuição
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [

                'em_preparacao',

                'aprovada',

                'em_transporte',

                'entregue',

                'recebida',

                'cancelada',

            ])->default('em_preparacao');

            /*
            |--------------------------------------------------------------------------
            | Transporte
            |--------------------------------------------------------------------------
            */

            $table->string('vehicle')
                ->nullable();

            $table->string('driver')
                ->nullable();

            $table->string('receipt_number')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Datas
            |--------------------------------------------------------------------------
            */

            $table->timestamp('prepared_at')
                ->nullable();

            $table->timestamp('dispatched_at')
                ->nullable();

            $table->timestamp('delivered_at')
                ->nullable();

            $table->timestamp('received_at')
                ->nullable();

            $table->timestamp('confirmed_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Confirmação
            |--------------------------------------------------------------------------
            */

            $table->boolean('confirmed')
                ->default(false);

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

            $table->index('distribution_number');

            $table->index('internal_request_id');

            $table->index('department_id');

            $table->index('status');

            $table->index('delivered_by');

            $table->index('received_by');

            $table->index('confirmed_by');

            $table->index('confirmed');

            $table->index('prepared_at');

            $table->index('delivered_at');

            $table->index('received_at');

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('distributions');

        Schema::enableForeignKeyConstraints();
    }
};
