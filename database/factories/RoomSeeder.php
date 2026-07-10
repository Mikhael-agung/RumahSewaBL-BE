<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        // Generate room untuk SEMUA building aktif (termasuk yang lama).
        // Kalau cuma mau isi building baru, filter dulu di sini sebelum jalan.
        $buildings = Building::whereNull('deleted_at')->get();

        if ($buildings->isEmpty()) {
            $this->command->warn('RoomSeeder: tidak ada building. Jalankan BuildingSeeder dulu. Skip.');
            return;
        }

        $totalRooms = 0;

        foreach ($buildings as $building) {
            // Cari nomor room_code tertinggi yang sudah dipakai di building ini,
            // biar gak nabrak unique (building_id, room_code) walau dijalankan berkali-kali.
            $existingCodes = Room::where('building_id', $building->id)
                ->withTrashed()
                ->pluck('room_code')
                ->map(fn ($code) => (int) preg_replace('/\D/', '', $code))
                ->filter()
                ->sort()
                ->values();

            $nextNumber = $existingCodes->isNotEmpty() ? $existingCodes->last() + 1 : 101;
            $roomCount = random_int(5, 8);

            for ($i = 0; $i < $roomCount; $i++) {
                Room::factory()->create([
                    'building_id' => $building->id,
                    'room_code' => 'R' . ($nextNumber + $i),
                ]);
            }

            $totalRooms += $roomCount;
        }

        $this->command->info("RoomSeeder: {$totalRooms} room baru dibuat di {$buildings->count()} building.");
    }
}
