<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cloudflare_domains', function (Blueprint $table) {
            $table->json('allowed_record_types')->after('prefix')->default('["A","AAAA","CNAME","SRV"]');
        });
    }

    public function down(): void
    {
        Schema::table('cloudflare_domains', function (Blueprint $table) {
            $table->dropColumn('allowed_record_types');
        });
    }
};
