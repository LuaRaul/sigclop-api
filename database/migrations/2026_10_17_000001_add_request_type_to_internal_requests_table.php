<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('internal_requests', 'request_type')) {
                $table->string('request_type', 30)->default('higiene')->after('request_number')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('internal_requests', function (Blueprint $table) {
            if (Schema::hasColumn('internal_requests', 'request_type')) {
                $table->dropIndex(['request_type']);
                $table->dropColumn('request_type');
            }
        });
    }
};
