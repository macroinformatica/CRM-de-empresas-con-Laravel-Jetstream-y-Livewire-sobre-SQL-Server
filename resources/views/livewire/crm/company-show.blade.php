<div>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('crm.companies') }}" wire:navigate
               class="text-sm text-gray-500 hover:text-gray-800 focus:outline-none focus-visible:underline">
                Empresas
            </a>
            <span class="text-gray-300" aria-hidden="true">/</span>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $company->name }}</h2>
        </div>
    </x-slot>

    @php
        $q = (int) $company->data_quality_score;
        $bar = $q < 40 ? 'bg-red-400' : ($q < 70 ? 'bg-amber-400' : 'bg-emerald-500');
        $fmt = fn ($d) => \Illuminate\Support\Carbon::parse($d, 'UTC')->setTimezone('America/Lima')->format('d/m/Y H:i');
        $noteTypes = [0 => 'Importada', 1 => 'Manual', 2 => 'Sistema'];
        $address = collect([$company->address_line1, $company->address_line2])->filter()->implode(', ');
        $place = collect([$company->city, $company->state_region, $company->country_name])->filter()->unique()->implode(', ');
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-3">

            {{-- Columna principal --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Contacto --}}
                <section class="bg-white shadow-sm sm:rounded-lg">
                    <h3 class="px-5 pt-5 text-base font-semibold text-gray-900">Cómo contactarla</h3>
                    <div class="grid gap-6 p-5 sm:grid-cols-2">
                        <div>
                            <h4 class="text-sm font-medium text-gray-600">Teléfonos</h4>
                            <ul class="mt-2 space-y-1.5 text-sm">
                                @forelse ($phones as $p)
                                    <li class="flex items-center gap-2">
                                        <a href="tel:+{{ $p->phone_digits }}" class="tabular-nums text-gray-900 hover:text-indigo-700">+{{ $p->phone_digits }}</a>
                                        @if ($p->is_primary)<span class="text-xs text-gray-500">principal</span>@endif
                                    </li>
                                @empty
                                    <li class="text-gray-400">Sin teléfonos</li>
                                @endforelse
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-600">Correos</h4>
                            <ul class="mt-2 space-y-1.5 text-sm">
                                @forelse ($emails as $e)
                                    <li class="flex items-center gap-2">
                                        <a href="mailto:{{ $e->email_normalized }}" class="break-all text-gray-900 hover:text-indigo-700">{{ $e->email_normalized }}</a>
                                        @if ($e->is_primary)<span class="text-xs text-gray-500">principal</span>@endif
                                    </li>
                                @empty
                                    <li class="text-gray-400">Sin correos</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="sm:col-span-2">
                            <h4 class="text-sm font-medium text-gray-600">Personas</h4>
                            <ul class="mt-2 divide-y divide-gray-100 text-sm">
                                @forelse ($contacts as $c)
                                    <li class="py-2">
                                        <span class="text-gray-900">{{ $c->full_name }}</span>
                                        @if ($c->job_title)<span class="text-gray-500"> · {{ $c->job_title }}</span>@endif
                                        @if ($c->is_primary)<span class="ml-1 text-xs text-gray-500">principal</span>@endif
                                    </li>
                                @empty
                                    <li class="py-2 text-gray-400">Sin personas de contacto</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </section>

                {{-- Notas --}}
                <section class="bg-white shadow-sm sm:rounded-lg">
                    <h3 class="px-5 pt-5 text-base font-semibold text-gray-900">Notas</h3>

                    <form wire:submit="addNote" class="p-5 border-b border-gray-100">
                        <label for="newNote" class="sr-only">Nueva nota</label>
                        <textarea id="newNote" wire:model="newNote" rows="3" placeholder="Escribe lo que pasó en la llamada o lo que falta hacer"
                                  class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        @error('newNote')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <div class="mt-2 flex justify-end">
                            <button type="submit" wire:loading.attr="disabled" wire:target="addNote"
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:opacity-50">
                                Guardar nota
                            </button>
                        </div>
                    </form>

                    <ul class="divide-y divide-gray-100">
                        @forelse ($notes as $n)
                            <li class="px-5 py-4 text-sm">
                                <p class="whitespace-pre-line text-gray-800">{{ $n->body }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $noteTypes[$n->note_type] ?? '' }} · {{ $fmt($n->created_at) }}</p>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-gray-500">Todavía no hay notas.</li>
                        @endforelse
                    </ul>
                </section>
            </div>

            {{-- Columna lateral --}}
            <aside class="space-y-6">
                <section class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-base font-semibold text-gray-900">Ficha</h3>
                        <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $company->status_name }}</span>
                    </div>

                    <dl class="mt-4 space-y-3 text-sm">
                        @if ($company->legal_name)
                            <div><dt class="text-gray-500">Razón social</dt><dd class="text-gray-900">{{ $company->legal_name }}</dd></div>
                        @endif
                        @if ($company->tax_id)
                            <div><dt class="text-gray-500">RUC / ID tributario</dt><dd class="tabular-nums text-gray-900">{{ $company->tax_id }}</dd></div>
                        @endif
                        <div>
                            <dt class="text-gray-500">Dirección</dt>
                            <dd class="text-gray-900">
                                {{ $address ?: '—' }}
                                @if ($place)<div class="text-gray-500">{{ $place }}</div>@endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Página web</dt>
                            <dd>
                                @if ($company->website_url)
                                    <a href="{{ $company->website_url }}" target="_blank" rel="noopener noreferrer"
                                       class="break-all text-indigo-600 hover:text-indigo-800">{{ $company->website_domain }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Categorías</dt>
                            <dd class="mt-1 flex flex-wrap gap-1.5">
                                @forelse ($categories as $cat)
                                    <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ $cat->name }}</span>
                                @empty
                                    <span class="text-gray-400">—</span>
                                @endforelse
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Calidad de datos</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                <div class="h-2 w-28 rounded-full bg-gray-200" role="img" aria-label="Calidad {{ $q }} de 100">
                                    <div class="h-2 rounded-full {{ $bar }}" style="width: {{ $q }}%"></div>
                                </div>
                                <span class="tabular-nums text-gray-700">{{ $q }} / 100</span>
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-5 border-t border-gray-100 pt-3 text-xs text-gray-500">
                        Creada el {{ $fmt($company->created_at) }} · Actualizada el {{ $fmt($company->updated_at) }}
                    </p>
                </section>
            </aside>
        </div>
    </div>
</div>
