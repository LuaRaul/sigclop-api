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
        Schema::create('document_categories', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identificação
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            $table->string('code', 30)->unique();

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Configurações
            |--------------------------------------------------------------------------
            */

            $table->boolean('requires_approval')
                ->default(false);

            $table->boolean('allow_attachment')
                ->default(true);

            $table->boolean('allow_versioning')
                ->default(true);

            $table->unsignedSmallInteger('retention_period')
                ->nullable()
                ->comment('Tempo de retenção em anos');

            $table->enum('confidential_level', [
                'publico',
                'interno',
                'confidencial',
                'restrito'
            ])->default('interno');

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $table->boolean('active')
                ->default(true);

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

            $table->index('code');

            $table->index('active');

            $table->index('requires_approval');

            $table->index('confidential_level');

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('document_categories');

        Schema::enableForeignKeyConstraints();
    }
};
