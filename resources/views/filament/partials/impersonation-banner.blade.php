@php
    $impersonatorId = session('impersonator_id');
    $currentUser = auth()->user();
    $tenant = \Filament\Facades\Filament::getTenant();
@endphp

@if ($impersonatorId && $currentUser)
    <div
        role="status"
        style="background-color: #f59e0b; color: #18181b; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; position: relative; z-index: 50; border-bottom: 1px solid #d97706;"
    >
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <x-heroicon-o-exclamation-triangle style="width: 1.125rem; height: 1.125rem; flex-shrink: 0;" />
            <span>
                Mode Impersonate: Anda sedang masuk sebagai <strong>{{ $currentUser->name }}</strong>
                @if ($tenant)
                    di sekolah <strong>{{ $tenant->name }}</strong>
                @endif
            </span>
        </div>

        <form method="POST" action="{{ route('impersonate.leave') }}" style="margin: 0;">
            @csrf
            <button
                type="submit"
                style="background-color: #18181b; color: #ffffff; border: none; border-radius: 0.375rem; padding: 0.35rem 0.75rem; font-size: 0.8125rem; font-weight: 600; cursor: pointer;"
            >
                Kembali ke SuperAdmin
            </button>
        </form>
    </div>
@endif
