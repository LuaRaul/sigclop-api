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
        Schema::create('approvals', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relacionamentos
            |--------------------------------------------------------------------------
            */

            $table->foreignId('internal_request_id')
                ->constrained('internal_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Fluxo de Aprovação
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger('approval_level')
                ->default(1);

            $table->enum('decision', [
                'pendente',
                'aprovado',
                'rejeitado',
                'reencaminhado',
                'cancelado'
            ])->default('pendente');

            /*
            |--------------------------------------------------------------------------
            | Comentários
            |--------------------------------------------------------------------------
            */

            $table->text('comment')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Auditoria
            |--------------------------------------------------------------------------
            */

            $table->timestamp('decided_at')
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

            $table->index('internal_request_id');

            $table->index('approved_by');

            $table->index('approval_level');

            $table->index('decision');

            $table->index('decided_at');

            $table->index('created_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('approvals');

        Schema::enableForeignKeyConstraints();
    }
};
