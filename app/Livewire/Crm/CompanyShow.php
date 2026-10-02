<?php

namespace App\Livewire\Crm;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CompanyShow extends Component
{
    public int $companyId;

    public string $newNote = '';

    public function mount(int $id): void
    {
        $exists = DB::table('crm.company')
            ->where('company_id', $id)->where('is_deleted', 0)->exists();

        abort_unless($exists, 404);

        $this->companyId = $id;
    }

    public function addNote(): void
    {
        $this->validate(
            ['newNote' => ['required', 'string', 'max:5000']],
            [
                'newNote.required' => 'Escribe la nota antes de guardarla.',
                'newNote.max'      => 'La nota no puede pasar de 5000 caracteres.',
            ]
        );

        DB::table('crm.note')->insert([
            'company_id' => $this->companyId,
            'body'       => trim($this->newNote),
            'note_type'  => 1, // manual
            'created_by' => auth()->id(),
        ]);

        $this->reset('newNote');
    }

    public function render()
    {
        $id = $this->companyId;

        $company = DB::table('crm.company as c')
            ->join('crm.company_status as s', 's.status_id', '=', 'c.status_id')
            ->leftJoin('crm.country as k', 'k.country_code', '=', 'c.country_code')
            ->where('c.company_id', $id)
            ->select(
                'c.company_id', 'c.name', 'c.legal_name', 'c.tax_id', 'c.website_url', 'c.website_domain',
                'c.address_line1', 'c.address_line2', 'c.city', 'c.state_region', 'c.postal_code',
                'c.data_quality_score', 'c.created_at', 'c.updated_at',
                'c.status_id', 's.name as status_name', 'k.name as country_name'
            )
            ->first();

        return view('livewire.crm.company-show', [
            'company'    => $company,
            'categories' => DB::table('crm.company_category as cc')
                ->join('crm.category as k', 'k.category_id', '=', 'cc.category_id')
                ->where('cc.company_id', $id)
                ->orderByDesc('cc.is_primary')->orderBy('k.name')
                ->get(['k.name', 'cc.is_primary']),
            'contacts'   => DB::table('crm.contact')
                ->where('company_id', $id)->where('is_deleted', 0)
                ->orderByDesc('is_primary')->orderBy('contact_id')
                ->get(['full_name', 'job_title', 'is_primary']),
            'phones'     => DB::table('crm.phone')
                ->where('company_id', $id)
                ->orderByDesc('is_primary')->orderBy('phone_id')
                ->get(['phone_raw', 'phone_digits', 'is_primary']),
            'emails'     => DB::table('crm.email')
                ->where('company_id', $id)
                ->orderByDesc('is_primary')->orderBy('email_id')
                ->get(['email_normalized', 'is_primary']),
            'notes'      => DB::table('crm.note')
                ->where('company_id', $id)
                ->orderByDesc('created_at')->orderByDesc('note_id')
                ->limit(100)
                ->get(['body', 'note_type', 'created_at']),
        ]);
    }
}
