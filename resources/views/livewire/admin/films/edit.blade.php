<?php

use App\Models\Film;
use App\Models\Genre;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Rule};
use Mary\Traits\Toast;

new
    #[Layout('components.layouts.admin')]
    #[Title('Edit Film')]
    class extends Component {
    use Toast;

    // Properti untuk menampung model Film yang sedang diedit
    public Film $film;

    // Properti untuk form, akan diisi di method mount()
    #[Rule('required|string|max:255')]
    public string $judul = '';

    #[Rule('required|string|max:255')]
    public string $sutradara = '';

    #[Rule('required|digits:4|integer|min:1900')]
    public string $tahun = '';

    #[Rule('required|exists:genres,id')]
    public string $genre_id = '';

    /**
     * Method mount() dieksekusi saat komponen dimuat.
     * Ia menerima objek $film secara otomatis dari Route Model Binding.
     */
    public function mount(Film $film): void
    {
        // Simpan instance film ke properti
        $this->film = $film;

        // Isi properti form dengan data dari film yang ada
        $this->judul = $film->judul;
        $this->sutradara = $film->sutradara;
        $this->tahun = $film->tahun;
        $this->genre_id = $film->genre_id;
    }

    /**
     * Method untuk menyimpan perubahan ke database.
     */
    public function update()
    {
        // Validasi semua input form
        $validated = $this->validate();

        // Update data film yang ada dengan data yang sudah divalidasi
        $this->film->update($validated);

        // Tampilkan notifikasi sukses
        $this->toast(
            type: 'success',
            title: 'Film Diperbarui!',
            description: 'Perubahan data film berhasil disimpan.',
            position: 'toast-bottom',
            icon: 'o-check-circle',
            timeout: 3000
        );

        // Arahkan kembali ke halaman daftar film
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
        <x-mary-header title="Edit Film" :subtitle="$film->judul" class="dark:text-white/90 mb-2!" separator />

        {{-- Form untuk mengedit film, wire:submit menargetkan method 'update' --}}
        <x-mary-form wire:submit="update">
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
                <x-mary-button label="Update" icon="o-paper-airplane" spinner="update" type="submit"
                    class="btn-primary dark:bg-stone-50 rounded-lg border-none hover:bg-stone-200 text-zinc-800" />
            </x-slot:actions>
        </x-mary-form>
    </div>
</div>