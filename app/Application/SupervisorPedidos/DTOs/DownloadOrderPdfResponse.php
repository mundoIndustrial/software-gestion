<?php

namespace App\Application\SupervisorPedidos\DTOs;

use Barryvdh\DomPDF\Facade\Pdf;

class DownloadOrderPdfResponse
{
    private $pdfContent;
    private string $filename;

    public function __construct($pdfContent, string $filename)
    {
        $this->pdfContent = $pdfContent;
        $this->filename = $filename;
    }

    public function getPdfContent()
    {
        return $this->pdfContent;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function download()
    {
        return $this->pdfContent->download($this->filename);
    }
}
