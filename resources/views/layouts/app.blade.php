@php
    $nav = [
        ['Dashboard', 'dashboard',          'dashboard',      'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
        ['Empresas',  'crm.companies',      'crm.companies*', 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21'],
        ['Importar',  'crm.import',         'crm.import*',    'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5'],
        ['Duplicados','crm.duplicates',     'crm.duplicates*','M16.5 8.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v8.25A2.25 2.25 0 006 16.5h2.25m8.25-8.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-7.5A2.25 2.25 0 018.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 00-2.25 2.25v6'],
        ['Segmentos', 'crm.segments',       'crm.segments*',  'M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z'],
        ['Exportar',  'crm.export',         'crm.export*',    'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3'],
    ];
    $item = 'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CRM Empresas') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="antialiased bg-[#f3f4f6] text-gray-800" style="font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;">
        <x-banner />

        <div x-data="{ open: false }" class="min-h-screen">

            {{-- Barra superior solo en móvil --}}
            <div class="lg:hidden flex items-center justify-between bg-[#1b2230] px-4 py-3">
                <span class="text-lg font-bold text-white">CRM <span class="text-teal-300">Empresas</span></span>
                <button type="button" @click="open = !open" class="text-slate-300" aria-label="Menú">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
                </button>
            </div>

            {{-- Sidebar --}}
            <aside :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                   class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col bg-[#1b2230] px-3.5 py-6 transition-transform lg:translate-x-0">
                <a href="{{ route('dashboard') }}" class="px-3 pb-8 text-xl font-bold text-white">
                    CRM <span class="text-teal-300">Empresas</span>
                </a>

                <nav class="flex flex-col gap-1">
                    @foreach ($nav as [$label, $route, $pattern, $icon])
                        @php $active = request()->routeIs($pattern); @endphp
                        <a href="{{ route($route) }}"
                           @class([$item, 'bg-teal-700 text-white' => $active, 'text-slate-300 hover:bg-white/5 hover:text-white' => ! $active])>
                            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                            <span class="flex-1">{{ $label }}</span>
                            @if ($route === 'crm.duplicates' && ! empty($duplicatesCount))
                                <span class="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-slate-900">{{ $duplicatesCount }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>

                <div class="mt-auto flex flex-col gap-1">
                    <a href="{{ route('profile.show') }}"
                       @class([$item, 'bg-teal-700 text-white' => request()->routeIs('profile.show'), 'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('profile.show')])>
                        <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        Mi perfil
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="{{ $item }} text-slate-300 hover:bg-white/5 hover:text-white">
                            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Contenido --}}
            <div class="lg:pl-60">
                @if (isset($header))
                    <header class="px-8 pt-8">{{ $header }}</header>
                @endif

                <main class="px-4 py-8 sm:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('modals')
        @livewireScripts
    </body>
</html>
