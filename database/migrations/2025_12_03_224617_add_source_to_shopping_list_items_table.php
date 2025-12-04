<?php

// database/migrations/2025_12_04_000000_add_source_to_shopping_list_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->string('source', 20)
                  ->nullable()
                  ->after('bought'); // or after 'unit' – up to you
        });
    }

    public function down(): void
    {
        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
