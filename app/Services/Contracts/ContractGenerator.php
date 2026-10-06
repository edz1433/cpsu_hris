<?php

namespace App\Services\Contracts;

use App\Models\ContractPeriod;
use App\Models\EmployeeContract;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use ZipArchive;

/**
 * Builds contract documents from resources/contract-templates.
 * Files are created under storage/app/contracts/tmp/{request-dir}, streamed, then deleted.
 */
class ContractGenerator
{
    /** Remove leftovers from requests that died before cleanup. */
    const STALE_AFTER_SECONDS = 3600;

    public function baseTmpDir(): string
    {
        return storage_path('app/contracts/tmp');
    }

    /** Fresh per-request working directory, deleted once the response is sent. */
    public function makeWorkDir(): string
    {
        $this->sweepStale();

        $dir = $this->baseTmpDir() . DIRECTORY_SEPARATOR . Str::uuid();
        File::ensureDirectoryExists($dir);

        app()->terminating(function () use ($dir) {
            File::deleteDirectory($dir);
        });

        return $dir;
    }

    /** Merge one contract into its type's template and return the .docx path. */
    public function generate(EmployeeContract $contract, string $dir): string
    {
        $period = $contract->period;
        $template = $this->templatePath($period);

        Settings::setTempDir($dir);
        // setValue() does not XML-escape by default; "&" or "<" in a position would corrupt the file.
        Settings::setOutputEscapingEnabled(true);

        $processor = new TemplateProcessor($template);
        foreach ($this->values($contract) as $key => $value) {
            $processor->setValue($key, $value);
        }

        $path = $dir . DIRECTORY_SEPARATOR . $this->fileName($contract);
        $processor->saveAs($path);

        return $path;
    }

    /** Merge several contracts and pack them into one .zip; returns the zip path. */
    public function zip($contracts, string $dir, string $zipName): string
    {
        $zipPath = $dir . DIRECTORY_SEPARATOR . $zipName;
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the ZIP file.');
        }

        $docsDir = $dir . DIRECTORY_SEPARATOR . 'docs';
        File::ensureDirectoryExists($docsDir);
        foreach ($contracts as $contract) {
            $docx = $this->generate($contract, $docsDir);
            $zip->addFile($docx, basename($docx));
        }
        $zip->close();

        return $zipPath;
    }

    public function values(EmployeeContract $contract): array
    {
        $period = $contract->period;
        $position = trim($contract->position);

        return [
            'EMPLOYEE_NAME'   => $contract->employee_name,
            'ARTICLE'         => preg_match('/^[aeiou]/i', $position) ? 'an' : 'a',
            'POSITION'        => $position,
            'MONTHLY_RATE'    => number_format((float) $contract->monthly_rate, 2),
            'DAILY_DEDUCTION' => number_format((float) $contract->daily_deduction, 2),
            'START_DATE'      => $period->start_date->format('F j, Y'),
            'END_DATE'        => $period->end_date->format('F j, Y'),
            'YEAR'            => $period->start_date->format('Y'),
            'REFERENCE_NO'    => $contract->reference_no,
        ];
    }

    public function fileName(EmployeeContract $contract): string
    {
        $name = preg_replace('/[^\p{L}\p{N} ._,-]+/u', '', $contract->reference_no . ' ' . $contract->employee_name);

        return trim($name) . '.docx';
    }

    public function templatePath(ContractPeriod $period): string
    {
        $file = $period->typeConfig()['template'] ?? null;
        $path = $file ? resource_path('contract-templates' . DIRECTORY_SEPARATOR . $file) : null;

        if (!$path || !is_file($path)) {
            throw new RuntimeException('No template is configured for ' . $period->typeLabel() . ' contracts.');
        }

        return $path;
    }

    /** Code used in reference numbers: campuses.short, then config, then campus_abbr letters. */
    public function campusCode($campus): string
    {
        if (!$campus) {
            return 'CPSU';
        }
        if (trim((string) $campus->short) !== '') {
            return strtoupper(trim($campus->short));
        }
        $configured = config('contracts.campus_codes.' . $campus->id);
        if ($configured) {
            return strtoupper($configured);
        }
        $letters = preg_replace('/[^A-Za-z0-9]/', '', (string) $campus->campus_abbr);

        return $letters !== '' ? strtoupper($letters) : 'C' . $campus->id;
    }

    /**
     * Next reference number, e.g. COSJO-KAB-S.2026-0004. Sequence is per campus per year
     * (period start year) across all periods. Must be called inside a DB transaction:
     * the matching employee_contracts rows are locked until commit.
     */
    public function nextReferenceNo(ContractPeriod $period, $campus): string
    {
        $prefix = ($period->typeConfig()['reference_prefix'] ?? 'COS') . '-'
            . $this->campusCode($campus) . '-S.' . $period->start_date->format('Y') . '-';

        $existing = EmployeeContract::withTrashed()
            ->where('reference_no', 'like', addcslashes($prefix, '%_\\') . '%')
            ->lockForUpdate()
            ->pluck('reference_no');

        $max = $existing->map(fn ($ref) => (int) substr($ref, strlen($prefix)))->max() ?? 0;

        return $prefix . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }

    protected function sweepStale(): void
    {
        $base = $this->baseTmpDir();
        if (!is_dir($base)) {
            return;
        }
        foreach (File::directories($base) as $dir) {
            if (filemtime($dir) < time() - self::STALE_AFTER_SECONDS) {
                File::deleteDirectory($dir);
            }
        }
    }
}
