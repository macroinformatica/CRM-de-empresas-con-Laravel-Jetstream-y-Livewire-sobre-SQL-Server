<?php

use App\Livewire\Crm\CompanyIndex;
use App\Livewire\Crm\CompanyShow;
use App\Livewire\Crm\ImportWizard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/empresas', CompanyIndex::class)->name('crm.companies');
    Route::get('/empresas/{id}', CompanyShow::class)->whereNumber('id')->name('crm.companies.show');
    Route::get('/importar', ImportWizard::class)->name('crm.import');

    // Secciones del menú que aún no tienen pantalla
    Route::get('/duplicados', fn () => view('crm.proximamente', ['titulo' => 'Duplicados']))->name('crm.duplicates');
    Route::get('/segmentos', fn () => view('crm.proximamente', ['titulo' => 'Segmentos']))->name('crm.segments');
    Route::get('/exportar', fn () => view('crm.proximamente', ['titulo' => 'Exportar']))->name('crm.export');
});
