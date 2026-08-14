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
        Schema::create('settings', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Configuração
            |--------------------------------------------------------------------------
            */

            $table->string('key',100)
                ->unique();

            $table->text('value')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Organização
            |--------------------------------------------------------------------------
            */

            $table->string('group',50)
                ->default('geral');

            $table->enum('type', [

                'text',

                'number',

                'boolean',

                'date',

                'json',

                'email',

                'url',

                'file',

            ])->default('text');

            /*
            |--------------------------------------------------------------------------
            | Descrição
            |--------------------------------------------------------------------------
            */

            $table->string('label')
                ->nullable();

            $table->text('description')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Segurança
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_public')
                ->default(false);

            $table->boolean('editable')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Auditoria
            |--------------------------------------------------------------------------
            */

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

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

            $table->index('key');

            $table->index('group');

            $table->index('type');

            $table->index('is_public');

            $table->index('editable');

            $table->index('updated_by');

            $table->index('created_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('settings');

        Schema::enableForeignKeyConstraints();
    }
};
