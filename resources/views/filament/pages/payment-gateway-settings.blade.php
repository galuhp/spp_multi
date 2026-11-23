<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        @if ($this->webhookUrl)
            <x-filament::section>
                <x-slot name="heading">
                    URL Webhook / Callback untuk Dashboard Payment Gateway
                </x-slot>
                <x-slot name="description">
                    Salin URL di bawah ini lalu tempelkan ke pengaturan Notification / Webhook URL pada dashboard provider Payment Gateway Anda.
                </x-slot>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <code class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-mono text-gray-800 select-all dark:border-white/10 dark:bg-white/5 dark:text-gray-200">
                        {{ $this->webhookUrl }}
                    </code>
                </div>
            </x-filament::section>
        @endif

        <div class="flex items-center gap-3">
            <x-filament::button type="submit" size="md">
                Simpan Pengaturan Payment Gateway
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
