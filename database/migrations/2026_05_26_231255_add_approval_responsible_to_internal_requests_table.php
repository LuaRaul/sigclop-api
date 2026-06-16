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
        Schema::table('internal_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_approver_id')->nullable()->after('requested_by');
            $table->timestamp('submitted_at')->nullable()->after('needed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internal_requests', function (Blueprint $table) {
            $table->dropColumn(['assigned_approver_id', 'submitted_at']);
        });
    }
};
