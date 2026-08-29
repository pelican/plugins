<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cloudflare_domains', function (Blueprint $table) {
            $table->string('prefix')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('cloudflare_domains', function (Blueprint $table) {
            $table->dropColumn('prefix');
        });
    }
};
