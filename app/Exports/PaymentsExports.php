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

/**
 * Export laporan pembayaran ke file Excel (.xlsx).
 *
 * Dipakai oleh {@see \App\Http\Controllers\Api\PaymentController::exportExcel()}
 * lewat `Excel::download(new PaymentsExport($payments), $filename)`. Koleksi
 * `$payments` yang dikirim ke constructor harus sudah eager-load relasi
 * `rental.tenant`, `rental.room.building`, dan `verifiedBy` (lihat
 * {@see \App\Services\PaymentService::exportPayments()}), karena method
 * {@see self::map()} mengakses relasi-relasi tersebut tanpa lazy-load ulang.
 */
class PaymentsExports implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * Koleksi Payment (beserta relasi ter-eager-load) yang akan di-export.
     *
     * @var Collection<int, Payment>
     */
    protected Collection $payments;

    /**
     * Mapping angka bulan (1-12) ke nama bulan Bahasa Indonesia,
     * dipakai buat kolom "Periode" di map().
     *
     * @var array<int, string>
     */
    private const MONTH_NAMES = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * @param Collection<int, Payment> $payments Koleksi payment terfilter yang mau di-export.
     */
    public function __construct(Collection $payments)
    {
        $this->payments = $payments;
    }

    /**
     * Sumber data baris (satu Payment = satu baris di sheet).
     *
     * @return Collection<int, Payment>
     */
    public function collection(): Collection
    {
        return $this->payments;
    }

    /**
     * Judul sheet Excel yang akan muncul di tab bawah file.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Laporan Pembayaran';
    }

    /**
     * Baris header (baris pertama) sheet, urutannya harus sinkron
     * dengan urutan array yang dikembalikan {@see self::map()}.
     *
     * @return array<int, string>
     */
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
     * Konversi satu record Payment jadi satu baris kolom di Excel.
     *
     * Urutan elemen array HARUS sinkron dengan {@see self::headings()}.
     * Mengakses relasi `rental.tenant`, `rental.room.building`, dan
     * `verifiedBy` — asumsinya sudah di-eager-load dari caller, kalau
     * belum bakal kena N+1 query per baris.
     *
     * @param Payment $payment Satu record payment dari collection().
     * @return array<int, string|float> Baris kolom siap tulis ke sheet.
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
            $payment->payment_method_label,
            str_replace('_', ' ', ucfirst($payment->payment_status)),
            $payment->verifiedBy->username ?? '-',
            $payment->notes ?? '-',
        ];
    }

    /**
     * Styling baris header: teks putih bold di atas background biru gelap
     * (`#0E5C7A`), biar header keliatan jelas kalau file dibuka di Excel.
     *
     * @param Worksheet $sheet Instance worksheet aktif dari PhpSpreadsheet.
     * @return array<int, array<string, mixed>> Style array ber-key nomor baris/kolom (dipakai PhpSpreadsheet).
     */
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