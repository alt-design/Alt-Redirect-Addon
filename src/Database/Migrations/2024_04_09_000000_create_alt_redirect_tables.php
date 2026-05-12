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
        Schema::create('alt_redirects', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('from')->index();
            $table->string('to');
            $table->integer('redirect_type')->default(301);
            $table->boolean('is_regex')->default(false);
            $table->json('sites')->nullable();
            $table->timestamps();
        });

        Schema::create('alt_query_strings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('query_string')->index();
            $table->boolean('strip')->default(false);
            $table->json('sites')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alt_redirects');
        Schema::dropIfExists('alt_query_strings');
    }
};
