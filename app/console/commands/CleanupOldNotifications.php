<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;

class CleanupOldNotifications extends Command
{
    protected $signature = 'notifications:cleanup {--days=365 : Hapus notifikasi terbaca yang lebih tua dari sekian hari}';

    protected $description = 'Hapus notifikasi yang sudah dibaca dan lebih tua dari batas hari tertentu';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = Notification::where('is_read', true)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Berhasil hapus {$deleted} notifikasi terbaca yang lebih tua dari {$days} hari.");

        return self::SUCCESS;
    }
}