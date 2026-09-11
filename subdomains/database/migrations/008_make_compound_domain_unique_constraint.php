<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cloudflare_domains')->whereNull('prefix')->update(['prefix' => '']);

        Schema::table('cloudflare_domains', function (Blueprint $table) {
            $table->string('prefix')->default('')->nullable(false)->change();

            $table->dropUnique(['name']);
            $table->unique(['name', 'prefix']);
        });
    }

    public function down(): void
    {
        // Deduplicate domain names
        $uniqueDomainIds = DB::table('cloudflare_domains')
            ->groupBy('name')
            ->select(DB::raw('MIN(id) as id'))
            ->pluck('id');

        DB::table('cloudflare_domains')
            ->whereNotIn('id', $uniqueDomainIds)
            ->update(['name' => DB::raw("CONCAT(name, '_', prefix, '_', id)")]);

        Schema::table('cloudflare_domains', function (Blueprint $table) {
            $table->dropUnique(['name', 'prefix']);
            $table->unique('name');

            $table->string('prefix')->nullable()->change();
        });
    }
};
