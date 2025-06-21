<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-neutral-950 antialiased">
    <flux:header container class="border-b border-zinc-200 bg-neutral-50 dark:border-zinc-700 dark:bg-neutral-950">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <a href="{{ route('home') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0"
            wire:navigate>
            <flux:icon.play-pause class="text-white/90" />
            <span class="text-lg font-semibold text-white/90">{{ config('app.name') }}</span>
        </a>

        <flux:spacer />

        <flux:separator vertical class="my-4 mx-2" />

        <!-- Desktop User Menu -->
        @auth
            <flux:dropdown position="top" align="end">
                <flux:profile class="cursor-pointer" :initials="auth()->user()->initials()" />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    @can('is-admin')
                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('admin.films.index')" icon="squares-2x2" wire:navigate>
                                {{ __('Dashboard') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />
                    @endcan

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />


                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        @else
            <a href="{{ route('login') }}"
                class="inline-block px-4 dark:text-white/90 text-[#1b1b18] dark:border-neutral-100 text-sm sm:text-base font-medium leading-normal dark:hover:text-white dark:hover:underline">
                Login
            </a>
        @endauth
    </flux:header>

    <!-- Mobile Menu -->
    <flux:sidebar stashable sticky
        class="lg:hidden border-e border-zinc-200 bg-neutral-50 dark:border-zinc-700 dark:bg-neutral-950">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('home') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0"
            wire:navigate>
            <flux:icon.play-pause class="text-white/90" />
            <span class="text-lg font-semibold text-white/90">{{ config('app.name') }}</span>
        </a>

        <flux:spacer />

    </flux:sidebar>

    {{ $slot }}

    <x-mary-toast />

    @fluxScripts

    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="{{ config('midtrans.client_key') }}"></script>

    <script type="text/javascript">
        document.addEventListener('livewire:initialized', () => {

            Livewire.on('snap-show', (event) => {
                // Panggil snap.embed, bukan snap.pay
                window.snap.embed(event.token, {
                    embedId: 'snap-container', // Target div
                    onSuccess: function (result) {
                        console.log('Payment Success:', result);
                        Livewire.dispatch('payment-success');
                    },
                    onPending: function (result) {
                        console.log('Payment Pending:', result);
                        // Anda bisa membuat listener khusus untuk 'payment-pending' jika perlu
                        Livewire.dispatch('payment-success'); // Anggap saja sama dengan sukses untuk UI
                    },
                    onError: function (result) {
                        console.log('Payment Error:', result);
                        Livewire.dispatch('payment-error');
                    },
                    onClose: function () {
                        // Saat pengguna menutup popup di dalam embed
                        console.log('Customer closed the payment embed.');
                        Livewire.dispatch('payment-cancelled');
                    }
                });
            });
        });
    </script>
</body>

</html>