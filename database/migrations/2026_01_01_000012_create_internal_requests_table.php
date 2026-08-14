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
        Schema::create('internal_requests', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identificação
            |--------------------------------------------------------------------------
            */

            $table->string('request_number', 50)
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Relacionamentos
            |--------------------------------------------------------------------------
            */

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('assigned_approver_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Fluxo
            |--------------------------------------------------------------------------
            */

            $table->enum('priority', [
                'baixa',
                'normal',
                'alta',
                'urgente'
            ])->default('normal');

            $table->enum('status', [
                'pendente',
                'em_analise',
                'aprovado',
                'rejeitado',
                'em_distribuicao',
                'concluido',
                'cancelado'
            ])->default('pendente');

            /*
            |--------------------------------------------------------------------------
            | Justificação
            |--------------------------------------------------------------------------
            */

            $table->text('justification')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Datas
            |--------------------------------------------------------------------------
            */

            $table->timestamp('needed_at')
                ->nullable();

            $table->timestamp('submitted_at')
                ->nullable();

            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamp('completed_at')
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

            $table->index('request_number');

            $table->index('department_id');

            $table->index('requested_by');

            $table->index('assigned_approver_id');

            $table->index('priority');

            $table->index('status');

            $table->index('needed_at');

            $table->index('submitted_at');

            $table->index('approved_at');

            $table->index('created_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('internal_requests');

        Schema::enableForeignKeyConstraints();
    }
};
