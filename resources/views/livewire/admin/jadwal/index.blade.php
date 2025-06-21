<?php

use App\Models\Film;
use App\Models\JadwalTayang;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
    #[Layout('components.layouts.admin')]
    #[Title('Manajemen Jadwal Tayang')]
    class extends Component {
    use WithPagination, Toast;

    public string $search = '';
    public string $selectedFilmId = ''; // Mengganti filter genre menjadi film
    public int $perPage = 10;

    // Mengatur ulang halaman saat filter/pencarian berubah
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedFilmId(): void
    {
        $this->resetPage();
    }

    public function filterByFilm(string $filmId): void
    {
        $this->selectedFilmId = $filmId;
    }

    // Default sorting untuk jadwal adalah berdasarkan tanggal & jam
    public array $sortBy = ['column' => 'tanggal', 'direction' => 'desc'];

    // Method untuk mengambil data jadwal
    public function with(): array
    {
        // Query dasar untuk model JadwalTayang
        $jadwalQuery = JadwalTayang::query()
            ->select(['id', 'film_id', 'studio_id', 'tanggal', 'jam', 'created_at'])
            ->with(['film:id,judul', 'studio:id,nama_studio']) // Eager load relasi yang dibutuhkan
            ->orderBy($this->sortBy['column'], $this->sortBy['direction']);

        // Terapkan filter pencarian berdasarkan judul film dari relasi
        if (!empty($this->search)) {
            $jadwalQuery->whereHas('film', function ($query) {
                $query->where('judul', 'like', '%' . $this->search . '%');
            });
        }

        // Terapkan filter berdasarkan film jika dipilih
        if ($this->selectedFilmId) {
            $jadwalQuery->where('film_id', $this->selectedFilmId);
        }

        // Siapkan data film untuk dropdown filter
        $filmsForFilter = Film::query()
            ->select(['id', 'judul'])
            ->orderBy('judul')
            ->get()
            ->map(function ($film) {
                return ['id' => $film->id, 'name' => $film->judul];
            })
            ->prepend(['id' => '', 'name' => 'Semua Film'])
            ->all();

        // Logika untuk menampilkan nama film yang dipilih di tombol dropdown
        $selectedFilmDisplayName = 'Film'; // Teks default
        if ($this->selectedFilmId) {
            $foundFilm = collect($filmsForFilter)->firstWhere('id', $this->selectedFilmId);
            if ($foundFilm && $foundFilm['name'] !== 'Semua Film') {
                $selectedFilmDisplayName = Str::limit($foundFilm['name'], 20); // Batasi panjang teks agar tidak merusak UI
            }
        }

        return [
            'jadwals' => $jadwalQuery->paginate($this->perPage),
            'filmsForFilter' => $filmsForFilter,
            'selectedFilmDisplayName' => $selectedFilmDisplayName,
        ];
    }

    /**
     * Method untuk menghapus jadwal.
     */
    public function delete(string $jadwalId): void
    {
        try {
            JadwalTayang::findOrFail($jadwalId)->delete();
            $this->toast(type: 'success', title: 'Jadwal Dihapus!', description: 'Jadwal tayang berhasil dihapus.', position: 'toast-bottom', icon: 'o-check-circle');
        } catch (ModelNotFoundException $e) {
            $this->toast(type: 'error', title: 'Gagal!', description: 'Jadwal tidak ditemukan.', position: 'toast-bottom', icon: 'o-x-circle');
        }
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- Header halaman --}}
        <x-mary-header title="Manajemen Jadwal Tayang" subtitle="Kelola semua jadwal tayang film"
            class="dark:text-white/90 mb-2!" separator />

        {{-- Tombol tambah & filter --}}
        <div class="flex w-full justify-between">
            <x-mary-button label="Tambah Jadwal" icon="o-plus" link="{{ route('admin.jadwal.create') }}"
                class="btn-md dark:bg-neutral-800 rounded-lg border-none hover:bg-neutral-700 shrink" wire:navigate />

            <flux:dropdown>
                <flux:navbar.item class="cursor-pointer" icon:trailing="chevron-down">{{ $selectedFilmDisplayName }}
                </flux:navbar.item>
                <flux:navmenu class="dark:bg-neutral-950!">
                    @foreach ($filmsForFilter as $film)
                        <flux:navmenu.item wire:click="filterByFilm('{{ $film['id'] }}')"
                            class="border-b-1! dark:border-neutral-700! rounded-none first:rounded-t-sm! last:rounded-b-sm! last:border-none cursor-pointer">
                            {{ $film['name'] }}
                        </flux:navmenu.item>
                    @endforeach
                </flux:navmenu>
            </flux:dropdown>
        </div>

        {{-- Input pencarian --}}
        <div class="flex w-full mb-1 gap-2 items-center">
            <flux:input wire:model.live.debounce.500ms="search" class:input="dark:bg-neutral-950!" kbd="⌘K"
                icon="magnifying-glass" placeholder="Cari berdasarkan judul film..." />
        </div>

        {{-- Definisi header tabel --}}
        @php
            $headers = [
                ['key' => 'film', 'label' => 'Film'],
                ['key' => 'studio', 'label' => 'Studio', 'sortable' => false],
                ['key' => 'tanggal', 'label' => 'Tanggal'],
                ['key' => 'jam', 'label' => 'Jam', 'sortable' => false],
                ['key' => 'actions', 'label' => '', 'sortable' => false],
            ];
        @endphp

        {{-- Tabel MaryUI --}}
        <x-mary-table :headers="$headers" :rows="$jadwals" :sort-by="$sortBy"
            class="text-zinc-800 dark:text-white/90 rounded-lg" with-pagination :per-page="$perPage">

            @scope('cell_film', $jadwal)
            {{ $jadwal->film->judul ?? 'N/A' }}
            @endscope

            @scope('cell_studio', $jadwal)
            {{ $jadwal->studio->nama_studio ?? 'N/A' }}
            @endscope

            @scope('cell_tanggal', $jadwal)
            {{ \Carbon\Carbon::parse($jadwal->tanggal)->translatedFormat('d M Y') }}
            @endscope

            @scope('cell_jam', $jadwal)
            {{ \Carbon\Carbon::parse($jadwal->jam)->format('H:i') }}
            @endscope

            {{-- Kolom Aksi (Edit & Delete) --}}
            @scope('cell_actions', $jadwal)
            <div class="flex items-center space-x-2">
                <x-mary-button icon="o-pencil-square" link="{{ route('admin.jadwal.edit', ['jadwal' => $jadwal->id]) }}"
                    spinner class="btn-sm dark:bg-neutral-950 rounded-lg border-none hover:bg-green-700"
                    wire:navigate />
                <x-mary-button icon="o-trash" wire:click="delete('{{ $jadwal->id }}')"
                    wire:confirm="Anda yakin ingin menghapus jadwal ini?" spinner
                    class="btn-sm dark:bg-neutral-950 border-none rounded-lg hover:bg-red-600" />
            </div>
            @endscope
        </x-mary-table>
    </div>
</div>