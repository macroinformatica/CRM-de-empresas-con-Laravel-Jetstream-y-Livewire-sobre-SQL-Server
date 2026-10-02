<?php

namespace App\Livewire\Crm;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class CompanyIndex extends Component
{
    private const PER_PAGE = 25;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $city = '';

    /** '' | no_email | no_phone | complete */
    #[Url(except: '')]
    public string $contact = '';

    #[Url(except: 1)]
    public int $page = 1;

    /** Cualquier cambio de filtro vuelve a la página 1. */
    public function updated(string $name): void
    {
        if ($name !== 'page') {
            $this->page = 1;
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'category', 'status', 'city', 'contact');
        $this->page = 1;
    }

    /** Chips: '' | no_email | no_phone | complete */
    public function setContact(string $value): void
    {
        $this->contact = in_array($value, ['no_email', 'no_phone', 'complete'], true) ? $value : '';
        $this->page = 1;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function render()
    {
        $hasEmail = match ($this->contact) {
            'no_email' => 0,
            'complete' => 1,
            default => null,
        };
        $hasPhone = match ($this->contact) {
            'no_phone' => 0,
            'complete' => 1,
            default => null,
        };

        $page = max(1, $this->page);

        // Se pide 1 fila de más para saber si existe una página siguiente
        // (el procedimiento no devuelve el total).
        $rows = collect(DB::select(
            'EXEC crm.usp_search_company
                @q = ?, @category_id = ?, @status_id = ?, @city = ?,
                @has_email = ?, @has_phone = ?, @offset = ?, @page_size = ?',
            [
                trim($this->search) !== '' ? trim($this->search) : null,
                $this->category !== '' ? (int) $this->category : null,
                $this->status !== '' ? (int) $this->status : null,
                $this->city !== '' ? $this->city : null,
                $hasEmail,
                $hasPhone,
                ($page - 1) * self::PER_PAGE,
                self::PER_PAGE + 1,
            ]
        ));

        $hasMore = $rows->count() > self::PER_PAGE;

        return view('livewire.crm.company-index', [
            'companies'  => $rows->take(self::PER_PAGE),
            'hasMore'    => $hasMore,
            'currentPage' => $page,
            'perPage'    => self::PER_PAGE,
            'categories' => Cache::remember('crm.categories', 600, fn () => DB::table('crm.category')
                ->where('is_active', 1)->orderBy('name')->get(['category_id', 'name'])),
            'statuses'   => Cache::remember('crm.statuses', 3600, fn () => DB::table('crm.company_status')
                ->orderBy('status_id')->get(['status_id', 'name'])),
            'cities'     => Cache::remember('crm.cities', 600, fn () => DB::table('crm.company')
                ->where('is_deleted', 0)->whereNotNull('city')
                ->distinct()->orderBy('city')->limit(300)->pluck('city')),
            'hasFilters' => $this->search !== '' || $this->category !== '' || $this->status !== ''
                || $this->city !== '' || $this->contact !== '',
        ]);
    }
}
