<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        $hasDuplicateSubdomains = DB::table('subdomains')
            ->select('name', 'domain_id')
            ->groupBy('name', 'domain_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateSubdomains) {
            throw new RuntimeException(
                'Cannot roll back subdomain coexistence while multiple record types share the same name and domain. Remove the duplicate records first.',
            );
        }

        Schema::table('subdomains', function (Blueprint $table) {
            $table->dropUnique(['name', 'domain_id', 'record_type']);
            $table->unique(['name', 'domain_id']);
        });
    }
};
