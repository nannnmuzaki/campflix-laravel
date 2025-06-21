<?php

use App\Models\JadwalTayang;
use App\Models\Genre;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;

new
    #[Layout('components.layouts.main')]
    #[Title('Jadwal Tayang - Campflix')]
    class extends Component {
    use WithPagination;

    public string $search = '';
    public string $selectedGenreId = '';
    public string $selectedDate = '';
    public string $sortDir = 'asc';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function filterByGenre(string $genreId): void
    {
        $this->selectedGenreId = $genreId;
    }

    public function filterByDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function toggleSort(): void
    {
        $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
    }

    public function with(): array
    {
        // Query dasar pada JadwalTayang
        $jadwalQuery = JadwalTayang::query()
            ->with(['film.genre', 'studio']) // Eager load relasi yang dibutuhkan
            ->whereDate('tanggal', '>=', today()); // Hanya tampilkan jadwal hari ini dan ke depan

        // Filter pencarian pada judul film
        if (!empty($this->search)) {
            $jadwalQuery->whereHas('film', function ($query) {
                $query->where('judul', 'like', '%' . $this->search . '%');
            });
        }

        // Filter genre pada film
        if ($this->selectedGenreId) {
            $jadwalQuery->whereHas('film', function ($query) {
                $query->where('genre_id', $this->selectedGenreId);
            });
        }

        // Filter tanggal
        if ($this->selectedDate) {
            $jadwalQuery->whereDate('tanggal', $this->selectedDate);
        }

        // [FIX] Terapkan sorting dan paginasi langsung ke query builder
        $jadwals = $jadwalQuery
            ->orderBy('tanggal', $this->sortDir)
            ->orderBy('jam', $this->sortDir)
            ->paginate(12); // Paginasi dilakukan di sini, BUKAN setelah get()

        // --- Sisanya sama seperti sebelumnya untuk mempersiapkan data filter ---
        $genresForFilter = Genre::orderBy('nama_genre')->get()->map(fn($g) => ['id' => $g->id, 'name' => $g->nama_genre])->prepend(['id' => '', 'name' => 'Semua Genre'])->all();
        $datesForFilter = collect(range(0, 6))->map(function ($day) {
            $date = today()->addDays($day);
            return ['id' => $date->format('Y-m-d'), 'name' => $date->translatedFormat('l, d M')];
        })->prepend(['id' => '', 'name' => 'Semua Tanggal'])->all();

        $selectedGenreDisplayName = 'Genre';
        if ($this->selectedGenreId && $found = collect($genresForFilter)->firstWhere('id', $this->selectedGenreId)) {
            $selectedGenreDisplayName = $found['name'];
        }
        $selectedDateDisplayName = 'Tanggal';
        if ($this->selectedDate && $found = collect($datesForFilter)->firstWhere('id', $this->selectedDate)) {
            $selectedDateDisplayName = $found['name'];
        }

        return [
            'jadwals' => $jadwals, // Kirim data jadwal yang sudah dipaginasi
            'genresForFilter' => $genresForFilter,
            'datesForFilter' => $datesForFilter,
            'selectedGenreDisplayName' => $selectedGenreDisplayName,
            'selectedDateDisplayName' => $selectedDateDisplayName,
        ];
    }
}; ?>

<div class="flex h-full w-5/6 mx-auto flex-1 flex-col gap-2 lg:gap-4 rounded-xl">
    <div class="flex flex-col lg:flex-row w-full gap-2 lg:gap-4 lg:-mt-2 items-center">
        <div class="w-full lg:w-xs">
            <flux:input wire:model.live.debounce.500ms="search" class:input="dark:bg-neutral-950!" kbd="⌘K"
                icon="magnifying-glass" placeholder="Search..." />
        </div>
        <div class="flex w-full items-center gap-2">
            <flux:dropdown>
                <flux:navbar.item class="cursor-pointer" icon:trailing="chevron-down">{{ $selectedGenreDisplayName }}
                </flux:navbar.item>
                <flux:navmenu class="dark:bg-neutral-950!">
                    @foreach ($genresForFilter as $genre)
                        {{-- Use wire:click to call filterByGenre method --}}
                        {{-- Pass the genre ID (which is $genre['id'] because of the map operation) --}}
                        <flux:navmenu.item wire:click="filterByGenre('{{ $genre['id'] }}')"
                            class="border-b-1! dark:border-neutral-700! rounded-none first:rounded-t-sm! last:rounded-b-sm! last:border-none text-white/80! cursor-pointer">
                            {{ $genre['name'] }} {{-- Access 'name' key from the mapped array --}}
                        </flux:navmenu.item>
                    @endforeach
                </flux:navmenu>
            </flux:dropdown>

            <flux:dropdown>
                <flux:navbar.item class="cursor-pointer" icon:trailing="chevron-down">{{ $selectedDateDisplayName }}
                </flux:navbar.item>
                <flux:navmenu class="dark:bg-neutral-950!">
                    @foreach ($datesForFilter as $date)
                        {{-- Use wire:click to call filterByDate method --}}
                        <flux:navmenu.item wire:click="filterByDate('{{ $date['id'] }}')"
                            class="border-b-1! dark:border-neutral-700! rounded-none first:rounded-t-sm! last:rounded-b-sm! last:border-none text-white/80! cursor-pointer">
                            {{ $date['name'] }}
                        </flux:navmenu.item>
                    @endforeach
                </flux:navmenu>
            </flux:dropdown>
            <x-mary-button :label="($sortDir == 'asc' ? 'Terdekat' : 'Terjauh')" icon="o-arrows-up-down"
                wire:click="toggleSort" class="max-[425px]:hidden btn-ghost rounded-lg sm:ml-auto" />
        </div>
    </div>
    <flux:separator class="my-0 mb-4 lg:my-2" />

    <div class="w-full grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <div class="min-[425px]:hidden flex items-center justify-end -my-4">
            <x-mary-button :label="($sortDir == 'asc' ? 'Terdekat' : 'Terjauh')" icon="o-arrows-up-down"
                wire:click="toggleSort" class="btn-ghost rounded-lg" />
        </div>
        @foreach ($jadwals as $jadwal)
            <a href="{{ route('jadwal.show', ['jadwal' => $jadwal->id]) }}"
                class="cursor-pointer flex flex-col w-full h-full" wire:navigate>
                {{-- Film Card --}}
                <x-mary-card
                    class="p-0! bg-[url(https://m.media-amazon.com/images/M/MV5BMjA1MTc1NTg5NV5BMl5BanBnXkFtZTgwOTM2MDEzNzE@._V1_FMjpg_UY2048_.jpg)] text-white/80 overflow-hidden relative rounded-lg w-full aspect-2/3 bg-cover bg-center">
                    <div
                        class="flex flex-col h-full w-full justify-end transition-opacity duration-500 ease-in-out opacity-0 hover:opacity-100">
                        <div
                            class="p-4 flex flex-col gap-1/2 text-white/90 font-medium leading-tight h-1/3 bg-linear-to-t from-neutral-950/60 to-neutral-950/80">
                            {{-- Judul Film --}}
                            <span>
                                {{ $jadwal->film->judul }}
                            </span>
                            {{-- Tahun rilis --}}
                            <span class="text-sm mt-auto">
                                {{ $jadwal->film->tahun }}
                            </span>
                            {{-- Sutradara --}}
                            <span class="text-sm">
                                {{ $jadwal->film->sutradara }}
                            </span>
                        </div>
                    </div>
                </x-mary-card>

                <div class="text-center mt-2">
                    <p class="font-semibold">{{ $jadwal->studio->nama_studio }}</p>
                    <p class="font-semibold text-lg text-yellow-400">
                        {{ $jadwal->tanggal->translatedFormat('d F') }} - {{ $jadwal->jam }}
                    </p>
                </div>
            </a>
        @endforeach
    </div>

    {{ $jadwals->links() }}

</div>