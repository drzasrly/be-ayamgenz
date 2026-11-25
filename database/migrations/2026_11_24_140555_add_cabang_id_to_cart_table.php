
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $foreignKeyExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'cart')
            ->where('COLUMN_NAME', 'cabang_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if (!$foreignKeyExists) {
            Schema::table('cart', function (Blueprint $table) {
                $table->foreign('cabang_id')
                    ->references('id')
                    ->on('cabangs')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('cart', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
        });
    }
};
