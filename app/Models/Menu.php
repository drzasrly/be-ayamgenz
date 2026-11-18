<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menu';

    protected $fillable = [
        'nama_menu',
        'harga',
        'kategori',
        'stok',
        'cabang_id',
        'gambar' 
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function cart()
    {
        return $this->hasMany(Cart::class, 'menu_id');
    }

    public function cabangStoks()
    {
        return $this->hasMany(MenuCabangStok::class, 'menu_id');
    }

    /**
     * Dapatkan jumlah stok menu untuk cabang tertentu.
     */
    public function getStokForCabang($cabangId): int
    {
        if (!$cabangId) {
            return (int) ($this->attributes['stok'] ?? 0);
        }

        // Cek jika relation cabangStoks sudah di-load
        if ($this->relationLoaded('cabangStoks')) {
            $record = $this->cabangStoks->firstWhere('cabang_id', $cabangId);
            return $record ? (int) $record->stok : 0;
        }

        $record = MenuCabangStok::where('menu_id', $this->id)
            ->where('cabang_id', $cabangId)
            ->first();

        return $record ? (int) $record->stok : 0;
    }

    /**
     * Kurangi stok menu untuk cabang tertentu.
     */
    public function decrementStokCabang($cabangId, int $qty): int
    {
        if (!$cabangId) {
            $this->decrement('stok', $qty);
            $this->refresh();
            return $this->stok;
        }

        $record = MenuCabangStok::firstOrCreate(
            ['menu_id' => $this->id, 'cabang_id' => $cabangId],
            ['stok' => 0]
        );

        $record->decrement('stok', $qty);
        $record->refresh();
        return $record->stok;
    }

    /**
     * Tambah stok menu untuk cabang tertentu.
     */
    public function incrementStokCabang($cabangId, int $qty): int
    {
        if (!$cabangId) {
            $this->increment('stok', $qty);
            $this->refresh();
            return $this->stok;
        }

        $record = MenuCabangStok::firstOrCreate(
            ['menu_id' => $this->id, 'cabang_id' => $cabangId],
            ['stok' => 0]
        );

        $record->increment('stok', $qty);
        $record->refresh();
        return $record->stok;
    }

    /**
     * Set stok spesifik untuk cabang tertentu.
     */
    public function setStokCabang($cabangId, int $stok): MenuCabangStok
    {
        return MenuCabangStok::updateOrCreate(
            ['menu_id' => $this->id, 'cabang_id' => $cabangId],
            ['stok' => $stok]
        );
    }

    /**
     * Mendapatkan nama cabang atau 'Semua Cabang' jika global.
     */
    public function getNamaCabangAttribute()
    {
        return $this->cabang ? $this->cabang->nama_cabang : 'Semua Cabang';
    }

    /**
     * Cek apakah menu berlaku untuk semua cabang.
     */
    public function isSemuaCabang(): bool
    {
        return is_null($this->cabang_id);
    }
}
