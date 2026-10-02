<div>
    @php
        $fields = \App\Livewire\Crm\ImportWizard::FIELDS;
        $field  = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 shadow-none focus:border-teal-700 focus:ring-1 focus:ring-teal-700';
        $btn    = 'rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-gray-50';
        $statusStyles = [
            'COMPLETED' => 'bg-teal-50 text-teal-700',
            'FAILED'    => 'bg-red-50 text-red-700',
            'LOADING'   => 'bg-amber-50 text-amber-800',
            'LOADED'    => 'bg-amber-50 text-amber-800',
        ];
        $statusNames = [
            'COMPLETED' => 'Completada',
            'FAILED'    => 'Falló',
            'LOADING'   => 'Cargando',
            'LOADED'    => 'Pendiente',
        ];
    @endphp

    {{-- Título --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-3xl font-bold text-slate-900">Importar</h1>
        <a href="{{ route('crm.companies') }}" wire:navigate class="{{ $btn }}">Volver a Empresas</a>
    </div>

    {{-- Error --}}
    @if ($error)
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            {{ $error }}
        </div>
    @endif

    {{-- Resultado de la importación --}}
    @if ($result)
        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-slate-900">Importación terminada</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $result['file_name'] ?? '' }}</p>

            <dl class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-sm text-gray-500">Filas leídas</dt>
                    <dd class="text-2xl font-bold tabular-nums text-slate-900">{{ $result['rows_total'] ?? 0 }}</dd>
                </div>
                @foreach (collect($result)->filter(fn ($v, $k) => str_starts_with($k, 'rows_') && $k !== 'rows_total' && is_numeric($v)) as $k => $v)
                    <div>
                        <dt class="text-sm text-gray-500">{{ \Illuminate\Support\Str::of($k)->after('rows_')->replace('_', ' ')->ucfirst() }}</dt>
                        <dd class="text-2xl font-bold tabular-nums text-slate-900">{{ $v }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('crm.companies') }}" wire:navigate
                   class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">
                    Ver empresas
                </a>
                <button type="button" wire:click="startOver" class="{{ $btn }}">Importar otro archivo</button>
                <button type="button" wire:click="rollback({{ (int) ($result['batch_id'] ?? 0) }})"
                        wire:confirm="¿Revertir esta importación? Se quitarán las empresas que trajo."
                        class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                    Revertir
                </button>
            </div>
        </div>
    @endif

    {{-- Paso 1: subir archivo --}}
    @if (! $result)
        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-slate-900">1. Elige el archivo</h2>
            <p class="mt-1 text-sm text-gray-500">Excel (.xlsx, .xls) o CSV, hasta 50 MB. La primera fila debe tener los encabezados.</p>

            <label for="file"
                   class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 px-6 py-10 text-center hover:border-teal-700 hover:bg-gray-50">
                <svg class="size-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                <span class="mt-2 text-sm font-medium text-slate-900">
                    @if ($file) {{ $file->getClientOriginalName() }} @else Haz clic para elegir un archivo @endif
                </span>
                <span wire:loading wire:target="file" class="mt-1 text-sm text-teal-700">Leyendo archivo…</span>
                <input id="file" type="file" wire:model="file" accept=".csv,.txt,.xlsx,.xls" class="sr-only">
            </label>
            @error('file')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    {{-- Paso 2: asignar columnas --}}
    @if (! $result && $file && $headers)
        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-slate-900">2. Asigna las columnas</h2>
            <p class="mt-1 text-sm text-gray-500">Ya adiviné cada columna por su nombre. Corrígelas si hace falta; cada campo se usa una sola vez.</p>

            @if ($sameFile)
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Este mismo archivo ya fue importado antes. Si lo vuelves a importar, se pueden generar duplicados.
                </div>
            @endif

            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            @foreach ($headers as $i => $h)
                                <th scope="col" class="px-3 py-3 font-medium whitespace-nowrap">{{ $h !== '' ? $h : 'Columna '.($i + 1) }}</th>
                            @endforeach
                        </tr>
                        <tr class="bg-white">
                            @foreach ($headers as $i => $h)
                                <th class="min-w-44 px-3 py-2 font-normal">
                                    <label for="map-{{ $i }}" class="sr-only">Campo para {{ $h }}</label>
                                    <select id="map-{{ $i }}" wire:model="mapping.{{ $i }}" class="{{ $field }}">
                                        <option value="">No importar</option>
                                        @foreach ($fields as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($preview as $row)
                            <tr>
                                @foreach ($headers as $i => $h)
                                    <td class="max-w-56 truncate px-3 py-2.5 text-slate-700">{{ $row[$i] ?? '' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
                <div class="w-56">
                    <label for="country" class="mb-1 block text-sm text-gray-500">País de los teléfonos</label>
                    <select id="country" wire:model="country" class="{{ $field }}">
                        @foreach ($countries as $c)
                            <option value="{{ $c->country_code }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="button" wire:click="startOver" class="{{ $btn }}">Cancelar</button>
                    <button type="button" wire:click="process" wire:loading.attr="disabled" wire:target="process"
                            class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800 disabled:opacity-60">
                        <span wire:loading.remove wire:target="process">Importar</span>
                        <span wire:loading wire:target="process">Importando…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Últimas importaciones --}}
    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white">
        <h2 class="px-6 pt-5 text-lg font-semibold text-slate-900">Últimas importaciones</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">Archivo</th>
                        <th scope="col" class="px-4 py-3 font-medium">Estado</th>
                        <th scope="col" class="px-4 py-3 font-medium">Filas</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($batches as $b)
                        <tr>
                            <td class="px-4 py-3 text-slate-900">
                                {{ $b->file_name }}
                                @if ($b->status === 'FAILED' && ! empty($b->error_message))
                                    <div class="mt-0.5 text-xs text-red-600">{{ \Illuminate\Support\Str::limit($b->error_message, 120) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusStyles[$b->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $statusNames[$b->status] ?? $b->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-slate-700">{{ $b->rows_total ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if (in_array($b->status, ['FAILED', 'LOADED'], true))
                                    <button type="button" wire:click="retry({{ $b->batch_id }})" wire:loading.attr="disabled"
                                            class="text-sm font-medium text-teal-700 hover:text-teal-900">Reintentar</button>
                                @elseif ($b->status === 'COMPLETED')
                                    <button type="button" wire:click="rollback({{ $b->batch_id }})"
                                            wire:confirm="¿Revertir esta importación? Se quitarán las empresas que trajo."
                                            class="text-sm font-medium text-red-700 hover:text-red-900">Revertir</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-gray-500">Aún no hay importaciones.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
