<?php

namespace App\Services\Crm;

use Generator;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Lee CSV o Excel y entrega filas como arrays de texto.
 * La primera fila yielded son los encabezados. Las filas vacías se saltan.
 */
class ImportFileReader
{
    public static function rows(string $path, string $ext): Generator
    {
        $ext = strtolower($ext);

        return in_array($ext, ['csv', 'txt'], true) ? self::csv($path) : self::sheet($path);
    }

    private static function csv(string $path): Generator
    {
        $h = fopen($path, 'r');
        $first = fgets($h) ?: '';
        rewind($h);
        if (str_starts_with($first, "\xEF\xBB\xBF")) {
            fseek($h, 3);
        }

        $delim = ',';
        if (substr_count($first, ';') > substr_count($first, ',')) {
            $delim = ';';
        } elseif (substr_count($first, "\t") > substr_count($first, ',')) {
            $delim = "\t";
        }

        while (($r = fgetcsv($h, 0, $delim)) !== false) {
            $row = array_map([self::class, 'clean'], $r);
            if (implode('', $row) !== '') {
                yield $row;
            }
        }
        fclose($h);
    }

    private static function sheet(string $path): Generator
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();

        foreach ($sheet->toArray(null, false, false, false) as $r) {
            $row = array_map([self::class, 'clean'], $r);
            if (implode('', $row) !== '') {
                yield $row;
            }
        }
    }

    private static function clean(mixed $v): string
    {
        if ($v === null) {
            return '';
        }
        if (is_float($v) && floor($v) === $v && abs($v) < 1e15) {
            return sprintf('%.0f', $v);          // 987654321.0 -> "987654321"
        }
        $v = trim((string) $v);

        return mb_check_encoding($v, 'UTF-8') ? $v : mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
    }
}
