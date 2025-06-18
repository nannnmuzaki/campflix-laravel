<?php

namespace App\Observers;

use App\Models\JadwalTayang;
use App\Models\Tiket;

class JadwalTayangObserver
{
    /**
     * Handle the JadwalTayang "created" event.
     */
    public function created(JadwalTayang $jadwalTayang): void
    {
        // Ambil kapasitas dari studio yang berelasi
        $kapasitas = $jadwalTayang->studio->kapasitas;

        // Buat tiket sebanyak kapasitas studio
        for ($i = 0; $i < $kapasitas; $i++) {
            Tiket::create([
                'jadwal_tayang_id' => $jadwalTayang->id,
                'harga' => 50000, // nanti dibuat dinamis
                'status' => 'tersedia',
            ]);
        }
    }
}