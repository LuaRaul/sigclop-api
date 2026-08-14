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
        Schema::create('documents', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relacionamentos
            |--------------------------------------------------------------------------
            */

            $table->foreignId('document_category_id')
                ->constrained('document_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Identificação
            |--------------------------------------------------------------------------
            */

            $table->string('title');

            $table->string('reference_number',100)
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Documento
            |--------------------------------------------------------------------------
            */

            $table->date('document_date')
                ->nullable();

            $table->string('file_name')
                ->nullable();

            $table->string('file_path')
                ->nullable();

            $table->string('mime_type',100)
                ->nullable();

            $table->unsignedBigInteger('file_size')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Versionamento
            |--------------------------------------------------------------------------
            */

            $table->string('version',20)
                ->default('1.0');

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $table->enum('status',[
                'rascunho',
                'pendente',
                'aprovado',
                'publicado',
                'arquivado',
                'cancelado'
            ])->default('rascunho');

            /*
            |--------------------------------------------------------------------------
            | Aprovação
            |--------------------------------------------------------------------------
            */

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Validade
            |--------------------------------------------------------------------------
            */

            $table->date('expires_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Resumo
            |--------------------------------------------------------------------------
            */

            $table->text('summary')
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

            $table->index('reference_number');

            $table->index('status');

            $table->index('document_date');

            $table->index('document_category_id');

            $table->index('department_id');

            $table->index('user_id');

            $table->index('approved_by');

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('documents');

        Schema::enableForeignKeyConstraints();
    }
};
