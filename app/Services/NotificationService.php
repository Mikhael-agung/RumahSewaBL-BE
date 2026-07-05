<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Kirim notifikasi ke satu user.
     *
     * @param int    $userId  ID user penerima.
     * @param string $title   Judul notifikasi.
     * @param string $message Isi pesan notifikasi.
     * @return Notification
     */
    public function send(int $userId, string $title, string $message): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'is_read' => false,
        ]);
    }

    /**
     * Kirim notifikasi yang sama ke semua user dengan role tertentu.
     *
     * @param array  $roles   Daftar nama role (contoh: ['manager', 'administrator']).
     * @param string $title   Judul notifikasi.
     * @param string $message Isi pesan notifikasi.
     * @return int Jumlah user yang menerima notifikasi.
     */
    public function sendToRoles(array $roles, string $title, string $message): int
    {
        $userIds = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->whereIn('roles.name', $roles)
            ->pluck('users.id');

        foreach ($userIds as $userId) {
            $this->send($userId, $title, $message);
        }

        return $userIds->count();
    }

    /**
     * Ambil daftar notifikasi milik user, terbaru duluan.
     *
     * @param int $userId
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getForUser(int $userId, int $perPage = 10)
    {
        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Hitung jumlah notifikasi yang belum dibaca milik user.
     *
     * @param int $userId
     * @return int
     */
    public function unreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     * Cuma bisa nandain notif milik sendiri.
     *
     * @param int $notificationId
     * @param int $userId
     * @return Notification
     * @throws \Exception 404 kalau notif gak ditemukan / bukan milik user ini.
     */
    public function markAsRead(int $notificationId, int $userId): Notification
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        return $notification->fresh();
    }

    /**
     * Tandai semua notifikasi milik user sebagai sudah dibaca.
     *
     * @param int $userId
     * @return int Jumlah notifikasi yang diupdate.
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}