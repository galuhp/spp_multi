@php
    $tenant = \Filament\Facades\Filament::getTenant();
    $announcements = $tenant instanceof \App\Models\Tenant
        ? \App\Models\TenantAnnouncement::query()->activeForTenant($tenant)->get()
        : collect();
@endphp

@if ($announcements->isNotEmpty())
    <div style="display: flex; flex-direction: column; gap: 0.625rem; margin-bottom: 1rem;">
        @foreach ($announcements as $announcement)
            @php
                $palette = match ($announcement->type) {
                    'success' => ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#065f46'],
                    'warning' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#92400e'],
                    'danger' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'text' => '#991b1b'],
                    default => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e40af'],
                };
            @endphp
            <div
                x-data="{ open: true }"
                x-show="open"
                style="background-color: {{ $palette['bg'] }}; border: 1px solid {{ $palette['border'] }}; color: {{ $palette['text'] }}; border-radius: 0.625rem; padding: 0.75rem 1rem; display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem;"
            >
                <div style="display: flex; align-items: flex-start; gap: 0.625rem; min-width: 0;">
                    <x-heroicon-o-megaphone style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; margin-top: 0.125rem;" />
                    <div style="font-size: 0.875rem; line-height: 1.4;">
                        <div style="font-weight: 600;">{{ $announcement->title }}</div>
                        <div style="margin-top: 0.125rem; opacity: 0.92;">{{ $announcement->message }}</div>
                    </div>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    aria-label="Tutup pengumuman"
                    style="background: transparent; border: none; color: inherit; cursor: pointer; padding: 0.125rem; opacity: 0.7;"
                >
                    <x-heroicon-o-x-mark style="width: 1.125rem; height: 1.125rem;" />
                </button>
            </div>
        @endforeach
    </div>
@endif
