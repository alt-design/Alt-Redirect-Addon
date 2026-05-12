<?php

use AltDesign\AltRedirect\Helpers\DefaultQueryStrings;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        (new DefaultQueryStrings)->makeDefaultQueryStrings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to remove them on down, they are part of the table data
    }
};
