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
            $table->string('distribution_number')->unique();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('internal_request_id')->nullable();
            $table->unsignedBigInteger('delivered_by')->nullable();
            $table->string('status')->default('em_preparacao');
            $table->timestamp('delivered_at')->nullable();
            $table->string('received_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};
