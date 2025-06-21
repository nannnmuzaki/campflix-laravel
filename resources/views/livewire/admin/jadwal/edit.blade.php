<?php

use App\Models\Film;
use App\Models\Studio;
use App\Models\JadwalTayang;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Rule};
use Illuminate\Validation\Rule as ValidationRule;
use Mary\Traits\Toast;

new
    #[Layout('components.layouts.admin')]
    #[Title('Edit Jadwal Tayang')]
    class extends Component {
    use Toast;

    // Properti untuk menampung model JadwalTayang yang akan diedit
    public JadwalTayang $jadwal;

    // Properti untuk form, akan diisi di method mount()
    #[Rule('required|exists:films,id')]
    public string $film_id = '';

    #[Rule('required|exists:studios,id')]
    public string $studio_id = '';

    #[Rule('required|date')]
    public string $tanggal = '';

    #[Rule('required')]
    public string $jam = '';

    /**
     * Method mount() menerima objek $jadwal dari Route Model Binding
     */
    public function mount(JadwalTayang $jadwal): void
    {
        // Simpan instance jadwal ke properti
        $this->jadwal = $jadwal;

        // Isi properti form dengan data dari jadwal yang ada
        $this->film_id = $jadwal->film_id;
        $this->studio_id = $jadwal->studio_id;
        $this->tanggal = $jadwal->tanggal;
        $this->jam = $jadwal->jam;
    }

    /**
     * Method untuk menyimpan perubahan ke database.
     */
    public function update()
    {
        $validated = $this->validate(
            [
                'film_id' => 'required|exists:films,id',
                'studio_id' => 'required|exists:studios,id',
                'tanggal' => 'required|date',
                'jam' => [
                    'required',
                    // Aturan validasi unik yang dimodifikasi
                    ValidationRule::unique('jadwal_tayang')->where(function ($query) {
                        return $query->where('studio_id', $this->studio_id)
                            ->where('tanggal', $this->tanggal)
                            ->where('jam', $this->jam);
                    })->ignore($this->jadwal->id), // <-- Abaikan record ini saat memeriksa duplikasi
                ],
            ],
            [
                'jam.unique' => 'Jadwal untuk studio, tanggal, dan jam yang sama sudah ada.'
            ]
        );

        // Update data jadwal yang ada
        $this->jadwal->update($validated);

        // Tampilkan notifikasi sukses
        $this->toast(
            type: 'success',
            title: 'Jadwal Diperbarui!',
            description: 'Perubahan jadwal tayang berhasil disimpan.',
            position: 'toast-bottom',
            icon: 'o-check-circle'
        );

        // Arahkan kembali ke halaman daftar jadwal
        return $this->redirect(route('admin.jadwal.index'), navigate: true);
    }

    /**
     * Menyiapkan data untuk dikirim ke view.
     */
    public function with(): array
    {
        // Mengambil data film dan studio untuk dropdown
        $films = Film::orderBy('judul')->get(['id', 'judul'])->map(fn($film) => ['id' => $film->id, 'name' => $film->judul])->all();
        $studios = Studio::orderBy('nama_studio')->get(['id', 'nama_studio'])->map(fn($studio) => ['id' => $studio->id, 'name' => $studio->nama_studio])->all();

        return [
            'films' => $films,
            'studios' => $studios
        ];
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- Header halaman --}}
        <x-mary-header title="Edit Jadwal Tayang" :subtitle="'Mengedit jadwal untuk: ' . $jadwal->film->judul"
            class="dark:text-white/90 mb-2!" separator />

        {{-- Form untuk mengedit jadwal, menargetkan method 'update' --}}
        <x-mary-form wire:submit="update">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Pilihan Film --}}
                <div class="md:col-span-full">
                    <x-mary-select label="Film" placeholder="Pilih Film" wire:model="film_id" :options="$films"
                        class="dark:text-white/90 dark:bg-neutral-950 rounded-md" />
                </div>

                <div class="md:col-span-full">
                    {{-- Pilihan Studio --}}
                    <x-mary-select label="Studio" placeholder="Pilih Studio" wire:model="studio_id" :options="$studios"
                        class="dark:text-white/90 dark:bg-neutral-950 rounded-md" />
                </div>

                {{-- Pilihan Tanggal --}}
                <x-mary-datepicker label="Tanggal Tayang" wire:model="tanggal"
                    class="dark:text-white/90 dark:bg-neutral-950 rounded-md" icon="o-calendar-days" />

                {{-- Pilihan Jam --}}
                <x-mary-datetime label="Jam Tayang" wire:model="jam" icon="o-clock" type="time"
                    class="dark:text-white/90 dark:bg-neutral-950 rounded-md" />
            </div>

            {{-- Tombol Aksi Form --}}
            <x-slot:actions>
                <x-mary-button label="Batal" link="{{ route('admin.jadwal.index') }}"
                    class="btn-primary dark:bg-neutral-700 rounded-lg border-none hover:bg-neutral-600" wire:navigate />
                <x-mary-button label="Update" icon="o-paper-airplane" spinner="update" type="submit"
                    class="btn-primary dark:bg-stone-50 rounded-lg border-none hover:bg-stone-200 text-zinc-800" />
            </x-slot:actions>
        </x-mary-form>
    </div>
</div>