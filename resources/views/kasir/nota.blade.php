<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota - {{ $invoice }}</title>

    <style>
        :root {
            --brand-red: #d90429;
            --brand-dark: #1f2937;
            --text-muted: #6b7280;
            --border-line: #d1d5db;
            --bg-page: #f3f4f6;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Arial, sans-serif;
            background: var(--bg-page);
            margin: 0;
            padding: 28px 16px;
            color: #111827;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .nota-wrapper {
            width: 100%;
            max-width: 340px;
        }

        .nota {
            width: 100%;
            background: #ffffff;
            padding: 22px 20px;
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(17, 24, 39, 0.10);
            border: 1px solid #e5e7eb;
            font-size: 12px;
            line-height: 1.45;
            position: relative;
        }

        .brand-header {
            text-align: center;
            padding-bottom: 10px;
        }

        .brand-badge {
            display: inline-block;
            background: var(--brand-red);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 999px;
            margin-bottom: 6px;
        }

        .brand-name {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 0.4px;
            color: var(--brand-dark);
            text-transform: uppercase;
        }

        .branch-name {
            margin-top: 3px;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }

        .branch-sub {
            margin-top: 2px;
            font-size: 10.5px;
            color: var(--text-muted);
        }

        .doc-title {
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--brand-dark);
            text-transform: uppercase;
            margin: 6px 0;
        }

        .divider {
            border-top: 1px dashed var(--border-line);
            margin: 10px 0;
        }

        .divider-strong {
            border-top: 1.5px dashed #374151;
            margin: 10px 0;
        }

        .meta-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 11.5px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .meta-label {
            color: var(--text-muted);
        }

        .meta-value {
            font-weight: 600;
            color: #111827;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-variant-numeric: tabular-nums;
        }

        .items-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .item-name {
            font-weight: 700;
            color: #111827;
            padding-top: 6px !important;
            font-size: 12px;
        }

        .items-table tr:first-child .item-name {
            padding-top: 0 !important;
        }

        .item-detail {
            color: var(--text-muted);
            font-size: 11px;
            padding-bottom: 4px !important;
        }

        .item-subtotal {
            text-align: right;
            font-weight: 600;
            color: #111827;
            font-size: 12px;
            white-space: nowrap;
            padding-bottom: 4px !important;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11.5px;
            margin: 3px 0;
            font-variant-numeric: tabular-nums;
        }

        .total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 9px 10px;
            margin: 8px 0;
            font-variant-numeric: tabular-nums;
        }

        .total-label {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.6px;
            color: var(--brand-dark);
        }

        .total-amount {
            font-size: 16px;
            font-weight: 800;
            color: var(--brand-red);
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .status-unpaid {
            background: #fef3c7;
            color: #92400e;
        }

        .footer-note {
            text-align: center;
            margin-top: 8px;
            font-size: 11px;
            color: var(--text-muted);
        }

        .footer-note .thanks {
            font-weight: 700;
            color: var(--brand-dark);
            font-size: 11.5px;
            margin-bottom: 2px;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            width: 100%;
        }

        .btn {
            flex: 1;
            padding: 11px 14px;
            border: none;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.12s ease, opacity 0.15s ease;
        }

        .btn:hover {
            opacity: 0.92;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-print {
            background: var(--brand-red);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(217, 4, 41, 0.25);
        }

        .btn-back {
            background: #ffffff;
            color: var(--brand-dark);
            border: 1px solid #d1d5db;
        }

        @media print {
            @page {
                size: 58mm auto;
                margin: 2mm;
            }

            body {
                background: #ffffff;
                padding: 0;
                margin: 0;
                display: block;
            }

            .nota-wrapper {
                width: 54mm;
                max-width: 54mm;
                margin: 0 auto;
            }

            .nota {
                width: 54mm;
                padding: 0;
                border: none;
                border-radius: 0;
                box-shadow: none;
                font-size: 10.5px;
                line-height: 1.35;
                color: #000000;
            }

            .brand-badge {
                display: none;
            }

            .brand-name {
                font-size: 13px;
                color: #000000;
            }

            .branch-name,
            .branch-sub,
            .meta-label,
            .meta-value,
            .item-name,
            .item-detail,
            .item-subtotal,
            .total-label,
            .total-amount,
            .footer-note,
            .footer-note .thanks {
                color: #000000 !important;
            }

            .divider,
            .divider-strong {
                border-top-color: #000000;
                margin: 6px 0;
            }

            .total-box {
                background: transparent;
                border: none;
                padding: 2px 0;
                margin: 4px 0;
            }

            .total-amount {
                font-size: 13px;
            }

            .status-badge {
                background: transparent;
                color: #000000;
                padding: 0;
                border-radius: 0;
            }

            .actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>

@php
    $firstTrx = $transaksi->first();
    $isPaid = $transaksi->every(fn($item) => $item->status === 'success');
    $totalQty = $transaksi->sum('qty');
@endphp

<div class="nota-wrapper">
    <div class="nota">

        <div class="brand-header">
            <span class="brand-badge">Struk Resmi</span>
            <h1 class="brand-name">Ayam Geprek GenZ</h1>
            <div class="branch-name">
                {{ $cabang->nama_cabang ?? 'Cabang #' . $firstTrx->cabang_id }}
            </div>
            @if(!empty($cabang?->alamat))
                <div class="branch-sub">{{ $cabang->alamat }}</div>
            @endif
            @if(!empty($cabang?->telepon))
                <div class="branch-sub">Telp: {{ $cabang->telepon }}</div>
            @endif
        </div>

        <div class="divider-strong"></div>
        <div class="doc-title">Nota Pembelian</div>
        <div class="divider"></div>

        <div class="meta-list">
            <!-- <div class="meta-row">
                <span class="meta-label">No. Invoice</span>
                <span class="meta-value">{{ $invoice }}</span>
            </div> -->
            <div class="meta-row">
                <span class="meta-label">Tanggal</span>
                <span class="meta-value">
                    {{ \Carbon\Carbon::parse($firstTrx->tanggal)->format('d/m/Y') }}
                </span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Waktu</span>
                <span class="meta-value">
                    {{ $firstTrx->created_at
                        ? $firstTrx->created_at->timezone('Asia/Jakarta')->format('H:i:s') . ' WIB'
                        : '-' }}
                </span>
            </div>
        </div>

        <div class="divider"></div>

        <table class="items-table">
            @foreach ($transaksi as $item)
                <tr>
                    <td colspan="2" class="item-name">
                        {{ $item->menu->nama_menu ?? 'Menu dihapus' }}
                    </td>
                </tr>
                <tr>
                    <td class="item-detail">
                        {{ $item->qty }} x Rp{{ number_format($item->qty > 0 ? $item->total / $item->qty : 0, 0, ',', '.') }}
                    </td>
                    <td class="item-subtotal">
                        Rp{{ number_format($item->total, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </table>

        <div class="divider-strong"></div>

        <div class="summary-row">
            <span class="meta-label">Total Item</span>
            <span class="meta-value">{{ $totalQty }} pcs</span>
        </div>

        <div class="total-box">
            <span class="total-label">TOTAL BAYAR</span>
            <span class="total-amount">Rp{{ number_format($total, 0, ',', '.') }}</span>
        </div>

        <div class="meta-list">
            <div class="meta-row">
                <span class="meta-label">Pembayaran</span>
                <span class="meta-value">{{ strtoupper($firstTrx->metode_pembayaran) }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Status</span>
                <span>
                    <span class="status-badge {{ $isPaid ? 'status-paid' : 'status-unpaid' }}">
                        {{ $isPaid ? 'LUNAS' : 'BELUM LUNAS' }}
                    </span>
                </span>
            </div>
        </div>

        <div class="divider"></div>

        <div class="footer-note">
            <div class="thanks">Terima kasih telah berbelanja!</div>
            <div>Selamat menikmati hidangan Anda :)</div>
        </div>

    </div>

    <div class="actions">
        <button type="button" class="btn btn-print" onclick="window.print()">
            Cetak Nota
        </button>
        <button type="button" class="btn btn-back" onclick="window.location.href='{{ route('kasir.create') }}'">
            Kembali ke Kasir
        </button>
    </div>
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
