<?php

namespace Database\Seeders;

use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class RentalSeeder extends Seeder
{
    public function run(): void
    {
        // Tenant yang belum punya rental aktif (biar gak dobel-sewa di skenario testing)
        $activeTenantIds = Rental::whereIn('rental_status', ['active', 'aktif'])->pluck('tenant_id')->toArray();
        $availableTenants = Tenant::whereNotIn('id', $activeTenantIds)->get();

        // Room yang statusnya 'available'
        $availableRooms = Room::where('room_status', 'available')->get()->shuffle();

        $creator = User::whereIn('role_id', [1, 2])->inRandomOrder()->first();
        if (! $creator) {
            $this->command->warn('RentalSeeder: tidak ada user admin/manager untuk created_by. Skip.');
            return;
        }

        if ($availableTenants->isEmpty() || $availableRooms->isEmpty()) {
            $this->command->warn('RentalSeeder: tidak ada tenant/room available. Skip.');
            return;
        }

        $count = min($availableTenants->count(), $availableRooms->count(), 15);
        $created = 0;

        foreach ($availableTenants->take($count) as $index => $tenant) {
            $room = $availableRooms[$index];

            // Distribusi status: mayoritas active, sebagian ended, 1-2 cancelled
            if ($created < 2 && $count > 10) {
                $status = 'cancelled';
            } elseif ($index % 4 === 0 && $created > 2) {
                $status = 'ended';
            } else {
                $status = 'active';
            }

            $startDate = now()->subMonths(random_int(1, 10))->startOfMonth();
            $endDate = null;

            if ($status === 'ended') {
                $endDate = (clone $startDate)->addMonths(random_int(1, 6));
            } elseif ($status === 'cancelled') {
                $endDate = (clone $startDate)->addDays(random_int(1, 20));
            }

            $rental = Rental::factory()->create([
                'tenant_id' => $tenant->id,
                'room_id' => $room->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'rental_status' => $status,
                'created_by' => $creator->id,
            ]);

            // Sinkronkan status kamar dengan status rental
            if ($status === 'active') {
                $room->update(['room_status' => 'occupied']);
            }

            $created++;
        }

        $this->command->info("RentalSeeder: {$created} rental baru dibuat.");
    }
}
