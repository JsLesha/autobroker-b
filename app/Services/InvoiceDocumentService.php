<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class InvoiceDocumentService
{
    public function generate(Invoice $invoice): Invoice
    {
        $invoice->loadMissing(['lot.vehicle', 'lot.client', 'lot.pricing', 'lot.buyer']);
        $html = view('invoices.document', [
            'invoice' => $invoice,
            'lot' => $invoice->lot,
        ])->render();

        $dir = 'invoices/'.$invoice->id;
        $htmlPath = $dir.'/invoice.html';
        Storage::disk('local')->put($htmlPath, $html);

        $invoice->forceFill(['html_path' => $htmlPath]);

        if (config('invoice.pdf_conversion')) {
            $pdfRelative = $this->convertHtmlToPdf($htmlPath, $dir);
            if ($pdfRelative) {
                $invoice->forceFill(['pdf_path' => $pdfRelative]);
            }
        }

        $invoice->save();

        return $invoice->fresh();
    }

    private function convertHtmlToPdf(string $htmlRelative, string $dir): ?string
    {
        $disk = Storage::disk('local');
        $htmlAbsolute = $disk->path($htmlRelative);
        $outDir = $disk->path($dir);
        $binary = $this->resolveBinary();
        if ($binary === null) {
            return null;
        }

        $profile = storage_path('app/libreoffice-profile');
        if (! is_dir($profile) && ! mkdir($profile, 0775, true) && ! is_dir($profile)) {
            return null;
        }

        $result = Process::timeout(45)->env([
            'HOME' => $profile,
        ])->run([
            $binary,
            '--headless',
            '--norestore',
            '--convert-to',
            'pdf',
            '--outdir',
            $outDir,
            $htmlAbsolute,
        ]);

        $pdfRelative = $dir.'/invoice.pdf';
        if ($result->successful() && $disk->exists($pdfRelative)) {
            return $pdfRelative;
        }

        return null;
    }

    private function resolveBinary(): ?string
    {
        $configured = (string) config('invoice.libreoffice');
        foreach ([$configured, 'soffice', 'libreoffice', '/usr/bin/soffice'] as $bin) {
            if ($bin === '') {
                continue;
            }
            $check = Process::timeout(5)->run([$bin, '--version']);
            if ($check->successful()) {
                return $bin;
            }
        }

        return null;
    }
}
