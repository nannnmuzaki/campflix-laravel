<?php

use App\Models\Film;
use App\Models\Genre;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
    #[Layout('components.layouts.admin')]
    #[Title('Manajemen Film')]
    class extends Component {
    use WithPagination, Toast;

    public string $search = '';
    public string $selectedGenreId = ''; // Diganti dari selectedCategoryId
    public int $perPage = 10;

    // Mengatur ulang halaman saat filter/pencarian berubah
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedGenreId(): void
    {
        $this->resetPage();
    }

    public function filterByGenre(string $genreId): void
    {
        $this->selectedGenreId = $genreId;
    }

    // Default sorting
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    // Method untuk mengambil data film
    public function with(): array
    {
        // Query dasar untuk model Film
        $filmsQuery = Film::query()
            ->select(['id', 'genre_id', 'judul', 'sutradara', 'tahun', 'created_at']) // Kolom disesuaikan untuk Film
            ->with([
                'genre' => function ($query) {
                    $query->select('id', 'nama_genre'); // Relasi ke Genre
                }
            ])
            ->orderBy(...array_values($this->sortBy));

        // Terapkan filter pencarian berdasarkan judul film
        if (!empty($this->search)) {
            $filmsQuery->where('judul', 'like', '%' . $this->search . '%');
        }

        // Terapkan filter genre jika dipilih
        if ($this->selectedGenreId) {
            $filmsQuery->where('genre_id', $this->selectedGenreId);
        }

        // Siapkan data genre untuk dropdown filter
        $genresForFilter = Genre::query()
            ->select(['id', 'nama_genre'])
            ->get()
            ->map(function ($genre) {
                return ['id' => $genre->id, 'name' => $genre->nama_genre]; // Sesuaikan dengan 'nama_genre'
            })
            ->prepend(['id' => '', 'name' => 'Semua Genre'])
            ->all();

        // Logika untuk menampilkan nama genre yang dipilih di tombol dropdown
        $selectedGenreDisplayName = 'Genre'; // Teks default
        if ($this->selectedGenreId) {
            $foundGenre = collect($genresForFilter)->firstWhere('id', $this->selectedGenreId);
            if ($foundGenre && $foundGenre['name'] !== 'Semua Genre') {
                $selectedGenreDisplayName = $foundGenre['name'];
            }
        }

        return [
            'films' => $filmsQuery->paginate($this->perPage), // Ubah 'products' menjadi 'films'
            'genresForFilter' => $genresForFilter,
            'selectedGenreDisplayName' => $selectedGenreDisplayName,
        ];
    }

    /**
     * Method untuk menghapus film.
     */
    public function delete(string $filmId): void
    {
        try {
            Film::findOrFail($filmId)->delete();

            $this->toast(
                type: 'success',
                title: 'Film Dihapus!',
                description: 'Data film berhasil dihapus.',
                position: 'toast-bottom',
                icon: 'o-check-circle',
                timeout: 3000
            );
        } catch (ModelNotFoundException $e) {
            $this->toast(type: 'error', title: 'Gagal Menghapus!', description: 'Film tidak ditemukan.', position: 'toast-bottom', icon: 'o-x-circle', timeout: 3000);
        } catch (\Exception $e) {
            $this->toast(type: 'error', title: 'Gagal Menghapus!', description: 'Terjadi kesalahan tak terduga.', position: 'toast-bottom', icon: 'o-x-circle', timeout: 3000);
        }

        $this->resetPage();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- Header halaman --}}
        <x-mary-header title="Manajemen Film" subtitle="Kelola semua data film" class="dark:text-white/90 mb-2!"
            separator />

        {{-- Tombol tambah & filter --}}
        <div class="flex w-full justify-between">
            <x-mary-button label="Tambah Film" icon="o-plus" link="{{ route('admin.films.create') }}"
                class="btn-md dark:bg-neutral-800 rounded-lg border-none hover:bg-neutral-700 shrink" wire:navigate />

            <flux:dropdown>
                <flux:navbar.item class="cursor-pointer" icon:trailing="chevron-down">{{ $selectedGenreDisplayName }}
                </flux:navbar.item>
                <flux:navmenu class="dark:bg-neutral-950!">
                    @foreach ($genresForFilter as $genre)
                        <flux:navmenu.item wire:click="filterByGenre('{{ $genre['id'] }}')"
                            class="border-b-1! dark:border-neutral-700! rounded-none first:rounded-t-sm! last:rounded-b-sm! last:border-none cursor-pointer">
                            {{ $genre['name'] }}
                        </flux:navmenu.item>
                    @endforeach
                </flux:navmenu>
            </flux:dropdown>
        </div>

        {{-- Input pencarian --}}
        <div class="flex w-full mb-1 gap-2 items-center">
            <flux:input wire:model.live.debounce.500ms="search" class:input="dark:bg-neutral-950!" kbd="⌘K"
                icon="magnifying-glass" placeholder="Cari berdasarkan judul..." />
        </div>

        {{-- Definisi header tabel --}}
        @php
            $headers = [
                ['key' => 'judul', 'label' => 'Judul Film'],
                ['key' => 'genre', 'label' => 'Genre', 'sortable' => false],
                ['key' => 'sutradara', 'label' => 'Sutradara'],
                ['key' => 'tahun', 'label' => 'Tahun'],
                ['key' => 'created_at', 'label' => 'Dibuat Pada'],
                ['key' => 'actions', 'label' => 'Actions', 'sortable' => false],
            ];
        @endphp

        {{-- Tabel MaryUI --}}
        <x-mary-table :headers="$headers" :rows="$films" :sort-by="$sortBy"
            class="text-zinc-800 dark:text-white/90 rounded-lg" with-pagination :per-page="$perPage">

            @scope('cell_genre', $film)
            <x-mary-badge :value="$film->genre->nama_genre ?? 'Tanpa Genre'" class="badge-primary rounded-sm" />
            @endscope

            @scope('cell_created_at', $film)
            {{ $film->created_at->format('d M Y') }}
            @endscope

            {{-- Kolom Aksi (Edit & Delete) --}}
            @scope('cell_actions', $film)
            <div class="flex items-center space-x-2">
                <x-mary-button icon="o-pencil-square" link="{{ route('admin.films.edit', ['film' => $film->id]) }}"
                    spinner class="btn-sm dark:bg-neutral-950 rounded-lg border-none hover:bg-green-700"
                    wire:navigate />
                <x-mary-button icon="o-trash" wire:click="delete('{{ $film->id }}')"
                    wire:confirm="Anda yakin ingin menghapus film ini?" spinner
                    class="btn-sm dark:bg-neutral-950 border-none rounded-lg hover:bg-red-600" />
            </div>
            @endscope
        </x-mary-table>
    </div>
</div>