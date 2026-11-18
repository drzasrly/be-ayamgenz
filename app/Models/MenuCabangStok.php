<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCabangStok extends Model
{
    protected $table = 'menu_cabang_stoks';

    protected $fillable = [
        'menu_id',
        'cabang_id',
        'stok',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }
}
