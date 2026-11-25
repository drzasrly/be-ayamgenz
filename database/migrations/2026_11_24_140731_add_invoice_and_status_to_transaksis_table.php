
<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom invoice dan status sudah tersedia.
        // Tidak perlu mengubah data transaksi yang ada.
    }

    public function down(): void
    {
        // Sengaja tidak menghapus invoice dan status.
        // Data transaksi lama harus tetap dipertahankan.
    }
};
