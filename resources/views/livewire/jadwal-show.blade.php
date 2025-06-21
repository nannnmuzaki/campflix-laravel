<?php

use App\Models\JadwalTayang;
use App\Models\Tiket;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, On};
use Illuminate\View\View;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

new
    #[Layout('components.layouts.main')]
    class extends Component {

    use Toast;

    public JadwalTayang $jadwal;
    public Tiket $selectedTiket;
    public string $nomorKursi = '';
    public int $jumlahTiketTersisa = 0;
    public bool $showBuyDrawer = false;
    public bool $snapModal = false;

    public function rendering(View $view): void
    {
        try {
            $view->title($this->jadwal->film->judul . ' - ' . $this->jadwal->jam);
        } catch (\Exception $e) {
            $view->title('Detail Jadwal | Campflix');
        }
    }

    /**
     * Mount komponen dan memuat relasi yang dibutuhkan.
     */
    public function mount(JadwalTayang $jadwal): void
    {
        // Eager load semua relasi yang akan kita gunakan di view untuk efisiensi
        $this->jadwal = $jadwal->load(['film.genre', 'studio', 'tikets']);
        $this->jumlahTiketTersisa = $this->jadwal->tikets->where('status', 'tersedia')->count();
    }

    public function updateJumlahTiketTersisa(): void
    {
        // Hitung ulang jumlah tiket yang tersedia
        $this->jadwal->load('tikets');
        $this->jumlahTiketTersisa = $this->jadwal->tikets->where('status', 'tersedia')->count();
    }

    public function updateNomorKursi(string $nomorKursi): void
    {
        // Update nomor kursi yang dipilih
        $this->nomorKursi = $nomorKursi;
    }

    /**
     * Aksi yang bisa dipanggil saat tiket diklik (untuk pengembangan selanjutnya).
     */
    public function selectTiket(string $tiketId, string $nomorKursi): void
    {
        // Logika untuk memilih tiket dan membuka drawer konfirmasi pembelian
        $this->selectedTiket = $this->jadwal->tikets->firstWhere('id', $tiketId);
        if ($this->selectedTiket && $this->selectedTiket->status == 'tersedia') {
            $this->updateNomorKursi($nomorKursi); // Update nomor kursi yang dipilih
            $this->showBuyDrawer = true; // Buka drawer untuk konfirmasi pembelian
        } else {
            $this->Error('Tiket tidak tersedia atau sudah terjual.');
            return;
        }
    }


    public function processPayment(string $tiketId): void
    {
        // Validasi apakah pengguna sudah login
        if (!Auth::check()) {
            $this->Error('Gagal', 'Anda harus login untuk melakukan pembelian tiket.');
            $this->showBuyDrawer = false;
            return;
        }

        // Validasi apakah tiket sudah dipilih
        if (!$this->selectedTiket) {
            $this->Error('Gagal', 'Silahkan pilih tiket terlebih dahulu.');
            $this->showBuyDrawer = false;
            return;
        }

        // Konfigurasi midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Generate order ID unik (dan nantinya seharusnya sih ini harus simpan di db, tapi karena gk ada disoal jadi sementara ngga dulu)
        $orderId = 'CAMPFLIX-' . time();

        // Parameter transaksi
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $this->selectedTiket->harga,
            ],
            'item_details' => [
                [
                    'id' => $this->selectedTiket->id,
                    'price' => $this->selectedTiket->harga,
                    'quantity' => 1,
                    'name' => 'Tiket: ' . $this->jadwal->film->judul . ' (Kursi ' . $this->nomorKursi . ')',
                ]
            ],
            'customer_details' => [
                'first_name' => Auth::user()->name ?? 'Guest User',
                'email' => Auth::user()->email ?? 'guest@example.com',
            ],
        ];

        // Proses pembayaran dengan Midtrans Snap
        try {
            // request snap token
            $snapToken = Snap::getSnapToken($params);

            // Update status tiket menjadi 'pending'
            $this->selectedTiket->status = 'pending';
            $this->selectedTiket->save();

            // Tutup drawer
            $this->showBuyDrawer = false;

            // Tampilkan modal snap
            $this->snapModal = true; // Tampilkan modal snap

            // Kirim token ke fronted untuk menampilkan popup midtrans snap
            $this->dispatch('snap-show', token: $snapToken);

        } catch (\Exception $e) {
            $this->Error('Payment Gateway Error', 'Terjadi kesalahan saat memproses pembayaran: ' . $e->getMessage());
        }
    }

    // #[On('payment-cancelled')] 
    public function paymentCancelled()
    {
        // Kembalikan status tiket ke 'tersedia' jika pembayaran dibatalkan
        if ($this->selectedTiket && $this->selectedTiket->status == 'pending') {
            $this->selectedTiket->status = 'tersedia';
            $this->selectedTiket->save();
        }

        $this->showBuyDrawer = false;
        $this->updateJumlahTiketTersisa();
        $this->toast(type: 'info', title: 'Pembayaran Dibatalkan');
    }

    #[On('payment-error')]
    public function paymentError()
    {
        // Logika untuk menangani kesalahan pembayaran
        if ($this->selectedTiket && $this->selectedTiket->status == 'pending') {
            $this->selectedTiket->status = 'tersedia';
            $this->selectedTiket->save();
        }

        $this->showBuyDrawer = false;
        $this->updateJumlahTiketTersisa();
        $this->toast(type: 'error', title: 'Pembayaran Gagal', description: 'Terjadi kesalahan saat memproses pembayaran.');
    }

    #[On('payment-pending')] 
    public function paymentPending()
    {
        // Logika untuk menangani pembayaran yang masih pending
        if ($this->selectedTiket && $this->selectedTiket->status == 'pending') {
            $this->toast(type: 'info', title: 'Pembayaran Pending', description: 'Pembayaran Anda sedang dalam proses.');
        }

        $this->showBuyDrawer = false;
    }

    #[On('payment-success')]
    public function paymentSuccess()
    {
        // Logika untuk menangani pembayaran sukses
        $this->selectedTiket->status = 'terjual';
        $this->selectedTiket->save();
        $this->updateJumlahTiketTersisa();
        $this->showBuyDrawer = false;
        $this->snapModal = false;
        $this->toast(type: 'success', title: 'Pembayaran Berhasil', description: 'Tiket Anda telah dibeli.');
    }
}; ?>

<div
    class="w-full h-full md:w-5/6 lg:max-h-screen grid grid-cols-1 lg:grid-cols-2 justify-items-center gap-8 mt-8 mx-auto rounded-lg mb-4 px-4">
    {{-- Kolom Poster --}}
    <div class="overflow-hidden flex aspect-[2/3] rounded-lg justify-self-center w-full">
        <img src="https://m.media-amazon.com/images/M/MV5BMjA1MTc1NTg5NV5BMl5BanBnXkFtZTgwOTM2MDEzNzE@._V1_FMjpg_UY2048_.jpg"
            alt="{{ $jadwal->film->judul }}" class="w-full h-full rounded-lg object-cover object-center shadow-2xl">
    </div>

    {{-- Kolom Informasi --}}
    <div class="flex flex-col w-full lg:min-h-0">
        {{-- Judul Film --}}
        <x-mary-badge :value="$jadwal->film->genre->nama_genre ?? 'N/A'"
            class="badge-primary mb-2 self-start rounded-md" />
        <h1 class="text-xl xl:text-4xl font-bold text-zinc-800 dark:text-white/90">{{ $jadwal->film->judul }}</h1>

        {{-- ... (Detail film) ... --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 text-sm text-gray-500 dark:text-gray-400">
            <x-mary-icon name="o-calendar" label="{{ $jadwal->film->tahun }}" class="w-4 h-4 inline-block mr-1" />
            <x-mary-icon name="o-user" label="{{ $jadwal->film->sutradara }}" class="w-4 h-4 inline-block mr-1" />
        </div>

        <flux:separator class="my-4" />

        {{-- Informasi Jadwal --}}
        <div class="flex flex-col items-center justify-between bg-yellow-400 text-neutral-900 rounded-lg p-4">
            <span class="text-neutral-900 font-bold text-2xl">{{ $jadwal->tanggal->translatedFormat('d F Y') }}
                - {{ $jadwal->jam }}
            </span>
            <x-mary-icon name="o-video-camera" label="{{ $jadwal->studio->nama_studio }}"
                class="w-6 h-6 text-neutral-900 font-bold text-xl" />
        </div>

        <flux:separator class="mb-4" />

        {{-- Tombol untuk melanjutkan ke pemilihan tiket --}}

        {{-- Ketersediaan Tiket --}}
        <div id="tiket" class="w-full mx-auto flex flex-col col-span-full lg:min-h-0 lg:grow">
            <x-mary-header class="w-full dark:text-white/90! mb-4!" size="text-xl xl:text-2xl"
                title="Ketersediaan Kursi" subtitle="Pilih kursi yang tersedia untuk melanjutkan." />

            <flux:separator class="mb-4" />

            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Tersisa: <span class="font-semibold">{{ $jumlahTiketTersisa }}</span> kursi
                </p>
            </div>

            {{-- Grid untuk menampilkan semua tiket --}}
            <div
                class="w-full lg:max-h-min flex-1 grid grid-cols-4 gap-3 sm:grid-cols-5 md:grid-cols-8 lg:grid-cols-6 lg:overflow-y-auto rounded-lg p-1">
                @forelse($jadwal->tikets as $index => $tiket)
                    {{-- Kartu untuk setiap tiket individu --}}
                    <div wire:key="{{ $tiket->id }}" @if($tiket->status == 'tersedia')
                        wire:click="selectTiket('{{ $tiket->id }}', '{{ $index + 1 }}')"
                        class="border border-green-600 bg-green-900/50 rounded-md p-2 text-center transition hover:bg-green-700 cursor-pointer"
                    title="Pilih Kursi {{ $index + 1 }}" @else
                            class="border border-red-600 bg-red-900/50 rounded-md p-2 text-center cursor-not-allowed opacity-50"
                        title="Kursi {{ $index + 1 }} (Terisi)" @endif>
                        <x-mary-icon name="o-user"
                            class="w-6 h-6 mx-auto {{ $tiket->status == 'tersedia' ? 'text-green-300' : 'text-red-300' }}" />
                        <p class="text-xs font-mono text-gray-300 dark:text-gray-400 mt-1">
                            {{-- Menampilkan nomor kursi sederhana --}}
                            {{-- Anda bisa menggantinya dengan data nomor kursi asli jika ada --}}
                            K-{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                        </p>
                    </div>
                @empty
                    <div class="col-span-full">
                        <x-mary-alert description="Tidak ada data tiket untuk jadwal ini." icon="o-exclamation-triangle"
                            class="alert-error" />
                    </div>
                @endforelse

                {{-- Drawer beli tiket --}}
                <x-mary-drawer wire:model="showBuyDrawer" title="Konfirmasi Pembelian Tiket"
                    subtitle="Pastikan nomor kursi sesuai dengan yang anda pilih." separator with-close-button
                    close-on-escape class="w-11/12 lg:w-1/3" right>
                    <div>
                        <div class="my-6">
                            {{-- Wadah tiket dengan efek sobekan --}}
                            <div class="bg-zinc-800 rounded-lg p-6 border-l-4 border-yellow-400 shadow-xl">

                                {{-- Header Tiket --}}
                                <div
                                    class="flex justify-between items-center pb-4 border-b-2 border-dashed border-gray-600">
                                    <div>
                                        <p class="text-sm text-gray-400">Bioskop</p>
                                        <p class="text-xl font-bold tracking-widest text-white">CAMPFLIX</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-400">Studio</p>
                                        <p class="text-xl font-bold text-white">{{ $jadwal->studio->nama_studio }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Detail Film & Jadwal --}}
                                <div class="mt-5">
                                    <p class="text-sm text-gray-400">Film</p>
                                    <h3 class="text-2xl font-bold text-white leading-tight">
                                        {{ $jadwal->film->judul }}
                                    </h3>

                                    <div class="grid grid-cols-2 gap-4 mt-6">
                                        <div>
                                            <p class="text-sm text-gray-400">Tanggal</p>
                                            <p class="text-lg font-semibold text-white">
                                                {{ $jadwal->tanggal->translatedFormat('d F Y') }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-400">Jam</p>
                                            <p class="text-lg font-semibold text-white">
                                                {{ $jadwal->jam }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-400">Kursi</p>
                                            <p class="text-lg font-semibold text-white">
                                                K-{{ str_pad($nomorKursi ?? 1, 2, '0', STR_PAD_LEFT) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-400">Harga</p>
                                            <p class="text-lg font-semibold text-white">Rp.
                                                {{ number_format($tiket->harga, 0, ',', '.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <x-slot:actions>
                            <x-mary-button class="rounded-lg" label="Batal" @click="$wire.buyDrawer = false" />
                            <x-mary-button class="btn-primary rounded-lg"
                                wire:click="processPayment('{{ $selectedTiket->id ?? '' }}')" label="Konfirmasi & Bayar"
                                icon="o-check" />
                        </x-slot:actions>
                </x-mary-drawer>

                <x-modal wire:model="snapModal" class="border-0! shadow-none! bg-transparent!" @close="$wire.paymentCancelled()">
                    <div id="snap-container" class="rounded-lg bg-transparent" wire:ignore>
                        </div>
                </x-modal>
            </div>
        </div>
    </div>
</div>