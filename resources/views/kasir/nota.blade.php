
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nota - {{ $invoice }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #eee;
            margin: 0;
            padding: 20px;
            color: #000;
        }

        .nota {
            width: 58mm;
            margin: auto;
            padding: 5px;
            background: white;
            font-size: 11px;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .line {
            border-top: 1px dashed black;
            margin: 8px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 5px;
            margin: 4px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 3px 0;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .total {
            font-size: 13px;
            font-weight: bold;
        }

        .actions {
            text-align: center;
            margin: 20px;
        }

        button {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: #222;
            color: white;
            border-radius: 5px;
            margin: 3px;
        }

        @media print {
            @page {
                size: 58mm auto;
                margin: 2mm;
            }

            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .nota {
                width: 54mm;
                margin: 0 auto;
                padding: 0;
            }

            .actions {
                display: none !important;
            }
        }
    </style>
</head>

<body>

<div class="nota">

    <div class="center">
        <h3 style="margin-bottom: 5px;">Ayam Geprek GenZ</h3>

        <div>
            {{ $cabang->nama_cabang ?? 'Cabang #' . $transaksi->first()->cabang_id }}
        </div>

        <div class="line"></div>

        <div class="bold">NOTA PEMBELIAN</div>
    </div>

    <!-- <div class="line"></div>

    <div>Invoice: {{ $invoice }}</div> -->

    <div>
        Tanggal:
        {{ \Carbon\Carbon::parse($transaksi->first()->tanggal)->format('d/m/Y') }}
    </div>

    <div>
        Waktu:
        {{ $transaksi->first()->created_at
            ? $transaksi->first()->created_at
                ->timezone('Asia/Jakarta')
                ->format('H:i:s')
            : '-'     }}
    </div>

    <div class="line"></div>

    <table>
        @foreach ($transaksi as $item)
            <tr>
                <td colspan="2" class="bold">
                    {{ $item->menu->nama_menu ?? 'Menu dihapus' }}
                </td>
            </tr>

            <tr>
                <td>
                    {{ $item->qty }} x
                    Rp{{ number_format(
                        $item->qty > 0 ? $item->total / $item->qty : 0,
                        0, ',', '.'
                    ) }}
                </td>

                <td class="right">
                    Rp{{ number_format($item->total, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach
    </table>

    <div class="line"></div>

    <div class="row total">
        <span>TOTAL</span>
        <span>Rp{{ number_format($total, 0, ',', '.') }}</span>
    </div>

    <div class="line"></div>

    <div class="row">
        <span>Pembayaran</span>
        <span>{{ strtoupper($transaksi->first()->metode_pembayaran) }}</span>
    </div>

    <div class="row">
        <span>Status</span>
        <span>
            {{ $transaksi->every(fn($item) => $item->status === 'success')
                ? 'LUNAS'
                : 'BELUM LUNAS' }}
        </span>
    </div>

    <div class="line"></div>

    <div class="center">
        <p>Terima kasih telah berbelanja!</p>
        <p>Selamat menikmati :)</p>
    </div>

</div>

<div class="actions">
    <button onclick="window.print()">
        CETAK NOTA
    </button>

    <button onclick="window.location.href='{{ route('kasir.create') }}'">
        KEMBALI KE KASIR
    </button>
</div>

<script>
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.print();
        }, 500);
    });
</script>

</body>
</html>
