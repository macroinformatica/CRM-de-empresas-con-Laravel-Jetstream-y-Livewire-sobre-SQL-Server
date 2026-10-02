<div>
    @php
        $field = 'w-full rounded-lg border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-800 shadow-none focus:border-teal-700 focus:ring-1 focus:ring-teal-700';
        $chips = [
            ''         => 'Todas',
            'no_email' => 'Sin correo',
            'no_phone' => 'Sin teléfono',
            'complete' => 'Completas',
        ];
    @endphp

    {{-- Título y acciones --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-3xl font-bold text-slate-900">Empresas</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('crm.import') }}" wire:navigate
               class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 hover:bg-gray-50">
                Importar archivo
            </a>
            {{-- Pendiente: apuntar a la pantalla/modal de alta de empresa --}}
            <a href="#"
               class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">
                + Nueva empresa
            </a>
        </div>
    </div>

    {{-- Búsqueda y filtros --}}
    <div class="mt-6 grid gap-3 md:grid-cols-[1fr_150px_130px_130px]">
        <label for="search" class="sr-only">Buscar</label>
        <input id="search" type="search" wire:model.live.debounce.400ms="search"
               placeholder="Buscar por nombre, correo, teléfono o web"
               class="{{ $field }}" autofocus>

        <label for="category" class="sr-only">Categoría</label>
        <select id="category" wire:model.live="category" class="{{ $field }}">
            <option value="">Categoría</option>
            @foreach ($categories as $c)
                <option value="{{ $c->category_id }}">{{ $c->name }}</option>
            @endforeach
        </select>

        <label for="status" class="sr-only">Estado</label>
        <select id="status" wire:model.live="status" class="{{ $field }}">
            <option value="">Estado</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->status_id }}">{{ $s->name }}</option>
            @endforeach
        </select>

        <label for="city" class="sr-only">Ciudad</label>
        <select id="city" wire:model.live="city" class="{{ $field }}">
            <option value="">Ciudad</option>
            @foreach ($cities as $name)
                <option value="{{ $name }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Chips --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            @foreach ($chips as $value => $label)
                <button type="button" wire:click="setContact('{{ $value }}')"
                        @class([
                            'rounded-full border px-4 py-2 text-sm font-medium transition-colors',
                            'border-teal-700 bg-teal-700 text-white' => $contact === (string) $value,
                            'border-gray-200 bg-white text-slate-900 hover:bg-gray-50' => $contact !== (string) $value,
                        ])>
                    {{ $label }}
                </button>
            @endforeach
            @if ($hasFilters)
                <button type="button" wire:click="clearFilters"
                        class="px-2 text-sm text-teal-700 hover:text-teal-900 focus:outline-none focus-visible:underline">
                    Quitar filtros
                </button>
            @endif
        </div>

        {{-- Pendiente: guardar los filtros actuales en la tabla de segmentos --}}
        <button type="button"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-gray-50">
            Guardar como segmento
        </button>
    </div>

    {{-- Tabla --}}
    <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div wire:loading.class="opacity-50"
             wire:target="search,category,status,city,contact,setContact,clearFilters,previousPage,nextPage"
             class="overflow-x-auto transition-opacity">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500">
                    <tr>
                        <th scope="col" class="px-4 py-3.5 font-medium">Empresa</th>
                        <th scope="col" class="px-4 py-3.5 font-medium">Categoría</th>
                        <th scope="col" class="px-4 py-3.5 font-medium">Ciudad</th>
                        <th scope="col" class="px-4 py-3.5 font-medium">Teléfono</th>
                        <th scope="col" class="px-4 py-3.5 font-medium">Correo</th>
                        <th scope="col" class="px-4 py-3.5 font-medium">Calidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($companies as $co)
                        @php
                            $q = (int) $co->data_quality_score;
                            $qColor = $q >= 90 ? 'text-teal-700' : ($q >= 40 ? 'text-orange-700' : 'text-red-600');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3.5">
                                <a href="{{ route('crm.companies.show', $co->company_id) }}" wire:navigate
                                   class="text-slate-900 hover:text-teal-700 focus:outline-none focus-visible:underline">
                                    {{ $co->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3.5 text-slate-700">{{ $co->category_name ?: '—' }}</td>
                            <td class="px-4 py-3.5 text-slate-700">{{ $co->city ?: '—' }}</td>
                            <td class="px-4 py-3.5 tabular-nums">
                                @if ($co->primary_phone)
                                    <span class="text-slate-700">+{{ $co->primary_phone }}</span>
                                @else
                                    <span class="text-orange-700">Sin teléfono</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                @if ($co->primary_email)
                                    <span class="break-all text-slate-700">{{ $co->primary_email }}</span>
                                @else
                                    <span class="text-orange-700">Sin correo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 font-bold tabular-nums {{ $qColor }}" title="{{ $q }} de 100">{{ $q }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center text-gray-500">
                                @if ($hasFilters)
                                    No hay empresas con esos filtros. Prueba quitando alguno.
                                @else
                                    Aún no hay empresas. Importa un archivo para empezar.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="flex items-center justify-between border-t border-gray-100 px-4 py-3 text-sm text-gray-500">
            <span>
                @if ($companies->isNotEmpty())
                    Mostrando {{ ($currentPage - 1) * $perPage + 1 }}–{{ ($currentPage - 1) * $perPage + $companies->count() }}
                @endif
            </span>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="previousPage" @disabled($currentPage <= 1)
                        class="rounded-lg border border-gray-200 bg-white px-4 py-2 font-medium text-slate-900 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                    Anterior
                </button>
                <button type="button" wire:click="nextPage" @disabled(! $hasMore)
                        class="rounded-lg border border-gray-200 bg-white px-4 py-2 font-medium text-slate-900 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                    Siguiente
                </button>
            </div>
        </div>
    </div>
</div>
