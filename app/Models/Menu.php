<?php

namespace App\Models;

use App\Models\MenuCabangStok;
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
        'gambar',
        'is_active' 
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
            return 0;
        }

        if ($this->relationLoaded('cabangStoks')) {
            $record = $this->cabangStoks->first(
                fn ($item) => (int) $item->cabang_id === (int) $cabangId
            );

            return (int) ($record?->stok ?? 0);
        }

        return (int) MenuCabangStok::where('menu_id', $this->id)
            ->where('cabang_id', $cabangId)
            ->value('stok');
    }

    public function decrementStokCabang($cabangId, int $qty): int
    {
        if (!$cabangId || $qty <= 0) {
            throw new \InvalidArgumentException('Cabang dan jumlah stok tidak valid.');
        }

        $affected = MenuCabangStok::where('menu_id', $this->id)
            ->where('cabang_id', $cabangId)
            ->where('stok', '>=', $qty)
            ->decrement('stok', $qty);

        if (!$affected) {
            throw new \RuntimeException('Stok tidak tersedia atau tidak mencukupi.');
        }

        return $this->getStokForCabang($cabangId);
    }

    public function incrementStokCabang($cabangId, int $qty): int
    {
        if (!$cabangId || $qty <= 0) {
            throw new \InvalidArgumentException('Cabang dan jumlah stok tidak valid.');
        }

        $record = MenuCabangStok::firstOrCreate(
            [
                'menu_id' => $this->id,
                'cabang_id' => $cabangId,
            ],
            ['stok' => 0]
        );

        $record->increment('stok', $qty);

        return (int) $record->fresh()->stok;
    }

    public function setStokCabang($cabangId, int $stok): MenuCabangStok
    {
        if (!$cabangId || $stok < 0) {
            throw new \InvalidArgumentException('Cabang atau jumlah stok tidak valid.');
        }

        return MenuCabangStok::updateOrCreate(
            [
                'menu_id' => $this->id,
                'cabang_id' => $cabangId,
            ],
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
