
<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Indeks unik user_id, menu_id, cabang_id
        // sudah tersedia dengan nama unique_user_menu_cabang.
    }

    public function down(): void
    {
        // Tidak menghapus indeks yang sudah ada.
    }
};
