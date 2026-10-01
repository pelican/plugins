<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subdomains', function (Blueprint $table) {
            $table->dropUnique(['name', 'domain_id']);
            $table->unique(['name', 'domain_id', 'record_type']);
        });
    }

    public function down(): void
    {
        Schema::table('subdomains', function (Blueprint $table) {
            $table->dropUnique(['name', 'domain_id', 'record_type']);
            $table->unique(['name', 'domain_id']);
        });
    }
};
