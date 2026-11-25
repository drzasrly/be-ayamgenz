
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_cabang_stoks', function (Blueprint $table) {
            $table->dropUnique('unique_menu_cabang');
        });
    }

    public function down(): void
    {
        Schema::table('menu_cabang_stoks', function (Blueprint $table) {
            $table->unique(
                ['menu_id', 'cabang_id'],
                'unique_menu_cabang'
            );
        });
    }
};
