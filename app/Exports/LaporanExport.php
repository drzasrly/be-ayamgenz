<?php

namespace App\Exports;

use App\Models\Transaksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LaporanExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected ?string $cabangId = null,
        protected ?string $metode = null,
        protected ?string $dari = null,
        protected ?string $sampai = null,
    ) {}

    public function collection()
    {
        $query = Transaksi::with(['menu', 'cabang'])
            ->where('status', 'success');

        if ($this->cabangId) {
            $query->where('cabang_id', $this->cabangId);
        }

        if ($this->metode) {
            $query->where('metode_pembayaran', $this->metode);
        }

        if ($this->dari && $this->sampai) {
            $query->whereBetween('tanggal', [$this->dari, $this->sampai]);
        }

        return $query->get()
            ->map(function ($t) {
                return [
                    $t->tanggal,
                    $t->cabang->nama_cabang ?? '-',
                    $t->menu->nama_menu ?? '-',
                    $t->qty,
                    strtoupper($t->metode_pembayaran),
                    $t->total,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Cabang',
            'Menu',
            'Qty',
            'Metode',
            'Total (Rp)',
        ];
    }
}

?>