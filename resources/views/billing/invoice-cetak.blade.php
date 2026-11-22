<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->doku_invoice_number ?: "INV-{$invoice->period}-{$invoice->id}" }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|jetbrains-mono:500,600,700" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 16mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 24px;
            background: #f1f5f9;
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            font-variant-numeric: tabular-nums;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .toolbar {
            max-width: 780px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            text-decoration: none;
        }
        .btn-primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }
        .sheet {
            max-width: 780px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 36px;
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.06);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e2e8f0;
        }
        .brand {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .muted {
            color: #64748b;
            font-size: 13px;
        }
        .mono {
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-variant-numeric: tabular-nums;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .badge-paid {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .badge-unpaid {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin: 24px 0;
        }
        .meta-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 6px;
        }
        .meta-value {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th {
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            background: #f8fafc;
            padding: 12px 14px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        td {
            padding: 14px;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        .text-right {
            text-align: right;
        }
        .summary {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .summary-box {
            width: 300px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
        }
        .summary-total {
            border-top: 2px solid #0f172a;
            margin-top: 8px;
            padding-top: 10px;
            font-size: 16px;
            font-weight: 800;
        }
        .footer {
            margin-top: 36px;
            padding-top: 16px;
            border-top: 1px dashed #cbd5e1;
            font-size: 12px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            gap: 16px;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .toolbar {
                display: none !important;
            }
            .sheet {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn" onclick="window.close()">Tutup</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <div class="sheet">
        <div class="header">
            <div>
                <div class="brand">{{ config('app.name', 'E-SPP Multi-Tenant') }}</div>
                <div class="muted" style="margin-top: 4px;">Invoice Tagihan Layanan Platform</div>
                <div class="mono" style="margin-top: 10px; font-size: 13px; font-weight: 600;">
                    No. Invoice: {{ $invoice->doku_invoice_number ?: "INV-{$invoice->period}-{$invoice->id}" }}
                </div>
            </div>
            <div style="text-align: right;">
                @if ($invoice->isPaid())
                    <span class="badge badge-paid">LUNAS</span>
                @else
                    <span class="badge badge-unpaid">BELUM DIBAYAR</span>
                @endif
                <div class="muted" style="margin-top: 10px;">
                    Periode: <strong style="color: #0f172a;">{{ $invoice->period }}</strong>
                </div>
            </div>
        </div>

        <div class="meta-grid">
            <div>
                <div class="meta-label">Ditagihkan Kepada</div>
                <div class="meta-value">{{ $tenant->name }}</div>
                <div class="muted" style="margin-top: 4px;">Kode Tenant: <span class="mono">{{ $tenant->slug }}</span></div>
                @if ($tenant->domain)
                    <div class="muted">Domain: {{ $tenant->domain }}</div>
                @endif
            </div>
            <div style="text-align: right;">
                <div class="meta-label">Informasi Pembayaran</div>
                <div class="muted">Tanggal Jatuh Tempo: <strong style="color: #0f172a;">{{ $invoice->due_date?->format('d M Y') ?? '-' }}</strong></div>
                <div class="muted" style="margin-top: 4px;">
                    Tanggal Lunas:
                    <strong style="color: #0f172a;">{{ $invoice->paid_at?->format('d M Y H:i') ?? '-' }}</strong>
                </div>
                <div class="muted" style="margin-top: 4px;">Metode: <strong style="color: #0f172a;">DOKU Checkout</strong></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Deskripsi Layanan</th>
                    <th class="text-right">Jumlah Transaksi</th>
                    <th class="text-right">Fee / Transaksi</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div style="font-weight: 600;">Biaya Platform Transaksi SPP (Periode {{ $invoice->period }})</div>
                        @if ($invoice->notes)
                            <div class="muted" style="margin-top: 2px;">{{ $invoice->notes }}</div>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($invoice->transaction_count, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($invoice->fee_per_transaction, 0, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: 600;">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-box">
                <div class="summary-row">
                    <span class="muted">Subtotal</span>
                    <span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total {{ $invoice->isPaid() ? 'Dibayar' : 'Tagihan' }}</span>
                    <span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <span>Dokumen ini diterbitkan secara elektronik dan sah tanpa tanda tangan basah.</span>
            <span>Dicetak: {{ now()->format('d M Y H:i') }}</span>
        </div>
    </div>
</body>
</html>
