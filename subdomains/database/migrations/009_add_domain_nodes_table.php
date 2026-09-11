<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloudflare_domain_node', function (Blueprint $table) {
            $table->unsignedInteger('node_id');
            $table->foreign('node_id')->references('id')->on('nodes')->cascadeOnDelete();

            $table->unsignedInteger('cloudflare_domain_id');
            $table->foreign('cloudflare_domain_id')->references('id')->on('cloudflare_domains')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['node_id', 'cloudflare_domain_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloudflare_domain_node');
    }
};
