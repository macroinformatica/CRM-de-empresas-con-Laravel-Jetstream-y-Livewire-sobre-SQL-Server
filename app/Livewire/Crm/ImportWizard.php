<?php

namespace App\Livewire\Crm;

use App\Services\Crm\ImportFileReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.app')]
class ImportWizard extends Component
{
    use WithFileUploads;

    /** campo de crm.stg_import => etiqueta que ve la persona */
    public const FIELDS = [
        'company_raw'  => 'Empresa (obligatorio)',
        'category_raw' => 'Categoría',
        'address1_raw' => 'Dirección',
        'address2_raw' => 'Dirección 2',
        'phone1_raw'   => 'Teléfono 1',
        'phone2_raw'   => 'Teléfono 2',
        'email1_raw'   => 'Correo 1',
        'email2_raw'   => 'Correo 2',
        'website_raw'  => 'Página web',
        'contact_raw'  => 'Persona de contacto',
        'notes_raw'    => 'Notas',
    ];

    /** largos máximos de crm.stg_import (notes_raw es MAX: sin límite) */
    private const LIMITS = [
        'company_raw' => 500, 'category_raw' => 300, 'address1_raw' => 600, 'address2_raw' => 600,
        'phone1_raw' => 100, 'phone2_raw' => 100, 'email1_raw' => 320, 'email2_raw' => 320,
        'website_raw' => 600, 'contact_raw' => 300,
    ];

    public $file;
    public array $headers = [];
    public array $preview = [];
    public array $mapping = [];
    public string $country = 'PE';
    public bool $sameFile = false;
    public ?array $result = null;
    public ?string $error = null;

    public function updatedFile(): void
    {
        $this->reset('headers', 'preview', 'mapping', 'result', 'error', 'sameFile');

        $this->validate(
            ['file' => ['required', 'file', 'max:51200', 'mimes:csv,txt,xlsx,xls']],
            ['file.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.', 'file.max' => 'El archivo supera los 50 MB.']
        );

        try {
            foreach (ImportFileReader::rows($this->file->getRealPath(), $this->file->getClientOriginalExtension()) as $i => $row) {
                if ($i === 0) {
                    $this->headers = $row;
                    continue;
                }
                $this->preview[] = $row;
                if (count($this->preview) >= 3) {
                    break;
                }
            }
        } catch (Throwable $e) {
            $this->error = 'No se pudo leer el archivo: ' . $e->getMessage();
            $this->file = null;

            return;
        }

        if (! $this->headers) {
            $this->error = 'El archivo está vacío.';
            $this->file = null;

            return;
        }

        $used = [];
        foreach ($this->headers as $i => $h) {
            $f = $this->guess($h, $used);
            if ($f) {
                $used[] = $f;
            }
            $this->mapping[$i] = $f;
        }

        $this->sameFile = DB::table('crm.import_batch')
            ->where('file_sha256', hash_file('sha256', $this->file->getRealPath()))
            ->where('status', 'COMPLETED')->exists();
    }

    public function process(): void
    {
        $this->error = null;
        $cols = array_filter($this->mapping);

        if (! $this->file) {
            $this->error = 'Primero sube un archivo.';

            return;
        }
        if (! in_array('company_raw', $cols, true)) {
            $this->error = 'Indica cuál columna es la empresa.';

            return;
        }
        if (count($cols) !== count(array_unique($cols))) {
            $this->error = 'Un mismo campo está asignado a dos columnas. Asigna cada campo una sola vez.';

            return;
        }

        set_time_limit(0);
        $batchId = null;

        try {
            $batchId = DB::table('crm.import_batch')->insertGetId([
                'source_id'   => DB::table('crm.source')->where('name', 'Importación Excel/CSV')->value('source_id'),
                'file_name'   => mb_substr($this->file->getClientOriginalName(), 0, 260),
                'file_sha256' => hash_file('sha256', $this->file->getRealPath()),
                'status'      => 'LOADING',
                'created_by'  => auth()->id(),
            ], 'batch_id');

            $total = $this->load($batchId, $cols);
            if ($total === 0) {
                throw new \RuntimeException('El archivo no tiene filas de datos.');
            }

            DB::table('crm.import_batch')->where('batch_id', $batchId)
                ->update(['status' => 'LOADED', 'rows_total' => $total]);

            $this->runBatch($batchId);
            $this->result = (array) DB::table('crm.import_batch')->where('batch_id', $batchId)->first();
            $this->reset('file', 'headers', 'preview', 'mapping', 'sameFile');
        } catch (Throwable $e) {
            if ($batchId) {
                DB::table('crm.import_batch')->where('batch_id', $batchId)->whereIn('status', ['LOADING', 'LOADED'])
                    ->update(['status' => 'FAILED', 'error_message' => mb_substr($e->getMessage(), 0, 2000)]);
            }
            $this->error = 'No se pudo importar: ' . $e->getMessage();
        }
    }

    public function retry(int $id): void
    {
        $this->error = null;
        try {
            set_time_limit(0);
            $this->runBatch($id);
        } catch (Throwable $e) {
            $this->error = 'No se pudo reintentar: ' . $e->getMessage();
        }
    }

    public function rollback(int $id): void
    {
        $this->error = null;
        try {
            DB::statement('EXEC crm.usp_import_rollback @batch_id = ?', [$id]);
            $this->result = null;
        } catch (Throwable $e) {
            $this->error = 'No se pudo revertir: ' . $e->getMessage();
        }
    }

    public function startOver(): void
    {
        $this->reset('file', 'headers', 'preview', 'mapping', 'result', 'error', 'sameFile');
    }

    public function render()
    {
        return view('livewire.crm.import-wizard', [
            'batches'   => DB::table('crm.import_batch')->orderByDesc('batch_id')->limit(8)->get(),
            'countries' => DB::table('crm.country')->orderBy('name')->get(['country_code', 'name']),
        ]);
    }

    /** Inserta las filas crudas en crm.stg_import. Devuelve cuántas filas cargó. */
    private function load(int $batchId, array $cols): int
    {
        $blank = array_fill_keys(array_keys(self::FIELDS), null);
        $buffer = [];
        $total = 0;

        foreach (ImportFileReader::rows($this->file->getRealPath(), $this->file->getClientOriginalExtension()) as $i => $row) {
            if ($i === 0) {
                continue;                                    // encabezados
            }
            $rec = ['batch_id' => $batchId, 'src_row' => $i + 1] + $blank;
            foreach ($cols as $idx => $field) {
                $v = $row[$idx] ?? '';
                $rec[$field] = $v === '' ? null : (isset(self::LIMITS[$field]) ? mb_substr($v, 0, self::LIMITS[$field]) : $v);
            }
            $buffer[] = $rec;
            $total++;

            if (count($buffer) === 150) {                    // 13 columnas x 150 = 1950 < 2100 parámetros de SQL Server
                DB::table('crm.stg_import')->insert($buffer);
                $buffer = [];
            }
        }
        if ($buffer) {
            DB::table('crm.stg_import')->insert($buffer);
        }

        return $total;
    }

    private function runBatch(int $batchId): void
    {
        $cc = DB::table('crm.country')->where('country_code', $this->country)->value('phone_cc');

        DB::statement(
            'EXEC crm.usp_import_process @batch_id = ?, @default_cc = ?, @default_country = ?, @detect_duplicates = 0, @created_by = ?',
            [$batchId, $cc, $this->country, auth()->id()]
        );
        DB::select('EXEC crm.usp_detect_duplicates');
    }

    /** Adivina el campo según el nombre de la columna del archivo. */
    private function guess(string $header, array $used): string
    {
        $h = Str::of($header)->ascii()->lower()->toString();
        $rules = [
            ['contact|responsable|persona', ['contact_raw']],
            ['mail|correo', ['email1_raw', 'email2_raw']],
            ['tel|cel|fono|movil|whats', ['phone1_raw', 'phone2_raw']],
            ['web|sitio|url|pagina', ['website_raw']],
            ['direc|address|ubicacion', ['address1_raw', 'address2_raw']],
            ['categ|rubro|giro', ['category_raw']],
            ['nota|observ|coment', ['notes_raw']],
            ['empresa|negocio|razon|company|nombre', ['company_raw']],
        ];
        foreach ($rules as [$pattern, $slots]) {
            if (preg_match("/($pattern)/", $h)) {
                foreach ($slots as $s) {
                    if (! in_array($s, $used, true)) {
                        return $s;
                    }
                }

                return '';
            }
        }

        return '';
    }
}
