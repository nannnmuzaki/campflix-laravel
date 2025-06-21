<?php

use App\Models\Film;
use App\Models\Genre;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Rule};
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new
    #[Layout('components.layouts.admin')]      // Menggunakan layout admin
    #[Title('Tambah Film Baru')]             // Judul halaman disesuaikan
    class extends Component {
    use WithFileUploads;
    use Toast;

    // --- Properti untuk Model Film ---
    #[Rule('required|string|max:255')]
    public string $judul = '';

    #[Rule('required|string|max:255')]
    public string $sutradara = '';

    #[Rule('required|digits:4|integer|min:1900')]
    public string $tahun = '';

    #[Rule('required|exists:genres,id')]
    public string $genre_id = '';

    /**
     * Method untuk menyimpan film baru ke database.
     */
    public function store()
    {
        // Validasi semua input form
        $this->validate();

        // Buat instance model Film baru
        $film = new Film();

        // Assign data dari properti ke model
        $film->judul = $this->judul;
        $film->sutradara = $this->sutradara;
        $film->tahun = $this->tahun;
        $film->genre_id = $this->genre_id;

        // Simpan data film ke database
        $film->save();

        // Tampilkan notifikasi sukses
        $this->toast(
            type: 'success',
            title: 'Film Ditambahkan!',
            description: 'Data film baru berhasil disimpan.',
            position: 'toast-bottom',
            icon: 'o-check-circle',
            timeout: 3000
        );

        // Arahkan ke halaman daftar film setelah berhasil
        return $this->redirect(route('admin.films.index'), navigate: true);
    }

    /**
     * Menyiapkan data untuk dikirim ke view.
     */
    public function with(): array
    {
        // Mengambil data genre untuk dropdown select
        $genres = Genre::orderBy('nama_genre')->get()->map(function ($genre) {
            return ['id' => $genre->id, 'name' => $genre->nama_genre];
        })->all();

        return [
            'genres' => $genres
        ];
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- Header halaman --}}
        <x-mary-header title="Tambah Film Baru" subtitle="Isi detail film di bawah ini" class="dark:text-white/90 mb-2!"
            separator />

        {{-- Form untuk menambah film baru --}}
        <x-mary-form wire:submit="store">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Judul Film --}}
                <div class="md:col-span-full">
                    <x-mary-input label="Judul Film" wire:model="judul" placeholder="Contoh: Interstellar"
                        class="dark:text-white/90 dark:bg-neutral-950 rounded-md" />
                </div>
                
                {{-- Sutradara --}}
                <div class="md:col-span-full">
                    <x-mary-input label="Sutradara" wire:model="sutradara" placeholder="Contoh: Christopher Nolan"
                        class="dark:text-white/90 dark:bg-neutral-950 rounded-md" />    
                </div>

                {{-- Genre --}}
                <x-mary-select label="Genre" placeholder="Pilih Genre" wire:model="genre_id" :options="$genres"
                    class="dark:text-white/90 dark:bg-neutral-950 rounded-md" />

                {{-- Tahun Rilis --}}
                <x-mary-input label="Tahun Rilis" wire:model="tahun" type="number" placeholder="Contoh: 2014"
                    class="dark:text-white/90 dark:bg-neutral-950 rounded-md" />

            </div>

            {{-- Tombol Aksi Form --}}
            <x-slot:actions>
                <x-mary-button label="Batal" link="{{ route('admin.films.index') }}"
                    class="btn-primary dark:bg-neutral-700 rounded-lg border-none hover:bg-neutral-600" wire:navigate />
                <x-mary-button label="Simpan" icon="o-paper-airplane" spinner="store" type="submit"
                    class="btn-primary dark:bg-stone-50 rounded-lg border-none hover:bg-stone-200 text-zinc-800" />
            </x-slot:actions>
        </x-mary-form>
    </div>
</div>

