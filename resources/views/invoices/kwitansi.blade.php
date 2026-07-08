<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $filename }}</title>
    <style>
        @page {
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1f2937;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        /* ===== Top accent bar ===== */
        .accent-bar {
            height: 10px;
            background-color: #0e5c7a;
            width: 100%;
        }

        .page {
            padding: 30px 45px 40px 45px;
        }

        /* ===== Header ===== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }
        .header-table td {
            vertical-align: top;
        }
        .brand-name {
            font-size: 20px;
            font-weight: bold;
            color: #0e5c7a;
            letter-spacing: 0.3px;
        }
        .brand-sub {
            font-size: 10.5px;
            color: #6b7280;
            margin-top: 2px;
        }
        .invoice-title {
            text-align: right;
            font-size: 22px;
            font-weight: bold;
            color: #111827;
            letter-spacing: 1px;
        }
        .invoice-code {
            text-align: right;
            font-size: 11px;
            color: #6b7280;
            margin-top: 3px;
        }

        .divider {
            border-top: 2px solid #e5e7eb;
            margin: 0 0 20px 0;
        }

        /* ===== Status badge ===== */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #ffffff;
        }
        .status-terverifikasi { background-color: #15803d; }
        .status-menunggu_verifikasi { background-color: #b45309; }
        .status-ditolak { background-color: #b91c1c; }

        /* ===== Info section (2 kolom) ===== */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }
        .info-table td {
            width: 50%;
            vertical-align: top;
            padding: 14px 16px;
            background-color: #f9fafb;
            border: 1px solid #eef0f2;
        }
        .info-table td.spacer {
            width: 14px;
            background-color: #ffffff;
            border: none;
            padding: 0;
        }
        .info-label {
            font-size: 9.5px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .info-value {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 10px;
        }
        .info-sub {
            font-size: 11px;
            color: #4b5563;
        }

        /* ===== Detail table ===== */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .detail-table th {
            background-color: #0e5c7a;
            color: #ffffff;
            text-align: left;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 10px 14px;
        }
        .detail-table th.text-right {
            text-align: right;
        }
        .detail-table td {
            padding: 11px 14px;
            border-bottom: 1px solid #eef0f2;
            font-size: 12px;
        }
        .detail-table td.text-right {
            text-align: right;
        }
        .detail-table tr:nth-child(even) td {
            background-color: #fafbfc;
        }

        /* ===== Total ===== */
        .total-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .total-table td {
            padding: 4px 0;
        }
        .total-label-cell {
            width: 65%;
        }
        .total-box {
            background-color: #0e5c7a;
            color: #ffffff;
            padding: 14px 18px;
            border-radius: 4px;
        }
        .total-box .t-label {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.85;
        }
        .total-box .t-amount {
            font-size: 20px;
            font-weight: bold;
            margin-top: 3px;
        }

        /* ===== Notes ===== */
        .notes-box {
            margin-top: 22px;
            padding: 12px 14px;
            background-color: #fffbeb;
            border-left: 3px solid #d97706;
            font-size: 11px;
            color: #78350f;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .rejection-box {
            margin-top: 22px;
            padding: 12px 14px;
            background-color: #fef2f2;
            border-left: 3px solid #b91c1c;
            font-size: 11px;
            color: #7f1d1d;
        }

        /* ===== Signature ===== */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 46px;
        }
        .sign-table td {
            width: 50%;
            text-align: center;
            font-size: 11px;
            color: #4b5563;
            vertical-align: top;
        }
        .sign-space {
            height: 55px;
        }
        .sign-name {
            font-weight: bold;
            color: #111827;
            border-top: 1px solid #9ca3af;
            padding-top: 6px;
            margin: 0 30px;
            display: block;
        }

        /* ===== Footer ===== */
        .footer {
            margin-top: 40px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
            font-size: 9.5px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="accent-bar"></div>
    <div class="page">

        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    <div class="brand-name">RUMAH SEWA BIRU LAUT</div>
                    <div class="brand-sub">
                        @if($building)
                            {{ $building->building_name }}<br>
                            {{ $building->building_address }}
                        @else
                            Sistem Manajemen Rumah Sewa
                        @endif
                    </div>
                </td>
                <td style="width: 45%;">
                    <div class="invoice-title">KWITANSI</div>
                    <div class="invoice-code">No. {{ $payment->payment_code }}</div>
                    <div class="invoice-code">
                        Tanggal: {{ \Carbon\Carbon::parse($payment->payment_date)->translatedFormat('d F Y') }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <!-- Info Penyewa & Kamar -->
        <table class="info-table">
            <tr>
                <td>
                    <div class="info-label">Ditagihkan Kepada</div>
                    <div class="info-value">{{ $tenant->full_name ?? '-' }}</div>
                    <div class="info-sub">{{ $tenant->phone_number ?? '' }}</div>
                    <div class="info-sub">{{ $tenant->email ?? '' }}</div>
                </td>
                <td class="spacer"></td>
                <td>
                    <div class="info-label">Detail Kamar</div>
                    <div class="info-value">{{ $room->room_code ?? '-' }}</div>
                    <div class="info-sub">{{ $building->building_name ?? '-' }}</div>
                    <div class="info-sub">Periode Sewa: {{ $periodLabel }}</div>
                </td>
            </tr>
        </table>

        <!-- Detail Pembayaran -->
        <table class="detail-table">
            <tr>
                <th>Keterangan</th>
                <th>Metode</th>
                <th class="text-right">Status</th>
            </tr>
            <tr>
                <td>Pembayaran sewa kamar periode <strong>{{ $periodLabel }}</strong></td>
                <td>{{ $paymentMethodLabel }}</td>
                <td class="text-right">
                    <span class="status-badge status-{{ $payment->payment_status }}">
                        {{ str_replace('_', ' ', $payment->payment_status) }}
                    </span>
                </td>
            </tr>
        </table>

        <!-- Total -->
        <table class="total-table">
            <tr>
                <td class="total-label-cell"></td>
                <td>
                    <div class="total-box">
                        <div class="t-label">Total Dibayar</div>
                        <div class="t-amount">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
                    </div>
                </td>
            </tr>
        </table>

        @if($payment->notes)
            <div class="notes-box">
                <div class="notes-title">Catatan</div>
                {{ $payment->notes }}
            </div>
        @endif

        @if($payment->payment_status === 'ditolak' && $payment->rejection_reason)
            <div class="rejection-box">
                <div class="notes-title">Alasan Penolakan</div>
                {{ $payment->rejection_reason }}
            </div>
        @endif

        <!-- Tanda tangan -->
        <table class="sign-table">
            <tr>
                <td>
                    <div class="sign-space"></div>
                    <span class="sign-name">{{ $tenant->full_name ?? 'Penyewa' }}</span>
                    Penyewa
                </td>
                <td>
                    <div class="sign-space"></div>
                    <span class="sign-name">{{ $verifiedByName ?? '(belum diverifikasi)' }}</span>
                    Manager / Administrator
                </td>
            </tr>
        </table>

        <div class="footer">
            Kwitansi ini digenerate otomatis oleh sistem pada {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB &mdash; Rumah Sewa Biru Laut
        </div>
    </div>
</body>
</html>