@php($profilIkon = \App\Models\ProfilSekolah::current())
@if ($profilIkon->logoUrl())
    <link rel="icon" href="{{ $profilIkon->logoUrl() }}">
    <link rel="apple-touch-icon" href="{{ $profilIkon->logoUrl() }}">
@endif
