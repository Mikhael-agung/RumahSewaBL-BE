<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected Collection $payments;

    private const MONTH_NAMES = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct(Collection $payments)
    {
        $this->payments = $payments;
    }

    public function collection(): Collection
    {
        return $this->payments;
    }

    public function title(): string
    {
        return 'Laporan Pembayaran';
    }

    public function headings(): array
    {
        return [
            'Kode Pembayaran',
            'Nama Penyewa',
            'Gedung',
            'Kamar',
            'Periode',
            'Jumlah (Rp)',
            'Tanggal Bayar',
            'Metode',
            'Status',
            'Diverifikasi Oleh',
            'Catatan',
        ];
    }

    /**
     * @param Payment $payment
     */
    public function map($payment): array
    {
        $tenant   = $payment->rental->tenant ?? null;
        $room     = $payment->rental->room ?? null;
        $building = $room->building ?? null;

        return [
            $payment->payment_code,
            $tenant->full_name ?? '-',
            $building->building_name ?? '-',
            $room->room_code ?? '-',
            (self::MONTH_NAMES[$payment->payment_month] ?? $payment->payment_month) . ' ' . $payment->payment_year,
            (float) $payment->amount,
            $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : '-',
            $payment->payment_method === 'manual' ? 'Manual/Tunai' : 'Transfer',
            str_replace('_', ' ', ucfirst($payment->payment_status)),
            $payment->verifiedBy->username ?? '-',
            $payment->notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Bold + background biru muda di baris header
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0E5C7A'],
                ],
            ],
        ];
    }
}