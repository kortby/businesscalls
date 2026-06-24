<?php

namespace App\Services;

class PdfGeneratorService
{
    private array $objects = [];

    /**
     * Generate pure PHP PDF report content.
     */
    public function generate(array $data): string
    {
        $title = $data['title'] ?? 'Executive Performance Report';
        $metrics = $data['metrics'] ?? [];

        // Build the content stream text
        $content = "BT\n";
        // Header
        $content .= "/F1 20 Tf\n";
        $content .= "10 TL\n";
        $content .= "0.0 0.47 0.29 rg\n"; // Emerald color (active emerald accent)
        $content .= "50 750 Td\n";
        $content .= '('.$this->escapeText($title).") Tj\n";
        $content .= "ET\n";

        // Horizontal Accent Line under Title
        $content .= "0.0 0.47 0.29 RG\n"; // Emerald stroke
        $content .= "2 w\n";
        $content .= "50 730 m\n";
        $content .= "545 730 l\n";
        $content .= "S\n";

        // Section Title: Key Metrics
        $content .= "BT\n";
        $content .= "/F1 14 Tf\n";
        $content .= "0.1 0.1 0.1 rg\n"; // Charcoal text
        $content .= "50 680 Td\n";
        $content .= "(Key Performance Indicators) Tj\n";
        $content .= "ET\n";

        // Draw Table Header Background
        $content .= "0.9 0.95 0.92 rg\n"; // Light mint background
        $content .= "50 630 495 25 re\n";
        $content .= "f\n";

        $content .= "BT\n";
        $content .= "/F1 11 Tf\n";
        $content .= "0.1 0.3 0.2 rg\n";
        $content .= "60 638 Td\n";
        $content .= "(Metric Description) Tj\n";
        $content .= "300 0 Td\n";
        $content .= "(Value) Tj\n";
        $content .= "ET\n";

        // Loop through metrics
        $y = 600;
        foreach ($metrics as $key => $val) {
            // Draw row border
            $content .= "0.8 0.8 0.8 RG\n";
            $content .= "0.5 w\n";
            $content .= "50 {$y} m\n";
            $content .= "545 {$y} l\n";
            $content .= "S\n";

            $content .= "BT\n";
            $content .= "/F1 10 Tf\n";
            $content .= "0.2 0.2 0.2 rg\n";
            $content .= '60 '.($y + 6)." Td\n";
            $content .= '('.$this->escapeText($key).") Tj\n";
            $content .= "300 0 Td\n";
            $content .= '('.$this->escapeText((string) $val).") Tj\n";
            $content .= "ET\n";

            $y -= 25;
        }

        // Draw bottom table border
        $content .= "0.8 0.8 0.8 RG\n";
        $content .= "0.5 w\n";
        $content .= "50 {$y} m\n";
        $content .= "545 {$y} l\n";
        $content .= "S\n";

        // Footer disclaimer
        $content .= "BT\n";
        $content .= "/F1 8 Tf\n";
        $content .= "0.5 0.5 0.5 rg\n";
        $content .= "50 50 Td\n";
        $content .= "(Confidential. Business Calls AI Executive Reports System.) Tj\n";
        $content .= "ET\n";

        return $this->buildPdf($content);
    }

    /**
     * Escape special PDF string characters.
     */
    private function escapeText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    /**
     * Build the raw PDF 1.4 document.
     */
    private function buildPdf(string $streamContent): string
    {
        $this->objects = [];

        // Object 1: Catalog
        $this->addObject('/Type /Catalog /Pages 2 0 R');

        // Object 2: Pages list
        $this->addObject('/Type /Pages /Kids [3 0 R] /Count 1');

        // Object 3: Page details
        $this->addObject('/Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 595 842] /Contents 5 0 R');

        // Object 4: Resources dictionary
        $this->addObject('/Font << /F1 6 0 R >>');

        // Object 5: Text content stream
        $this->addObject('/Length '.strlen($streamContent), $streamContent);

        // Object 6: Font configuration
        $this->addObject('/Type /Font /Subtype /Type1 /BaseFont /Helvetica');

        // Compile output and catalog starting byte positions
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($this->objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n";
            if (isset($obj['stream'])) {
                $pdf .= '<< '.$obj['header']." >>\n";
                $pdf .= "stream\n".$obj['stream']."\nendstream\n";
            } else {
                $pdf .= '<< '.$obj['header']." >>\n";
            }
            $pdf .= "endobj\n";
        }

        // Cross-reference table (xref)
        $xrefStart = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= '0 '.(count($this->objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($this->objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        // Trailer
        $pdf .= "trailer\n";
        $pdf .= '<< /Size '.(count($this->objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= "{$xrefStart}\n";
        $pdf .= '%%EOF';

        return $pdf;
    }

    /**
     * Push a PDF object block mapping.
     */
    private function addObject(string $header, ?string $stream = null): void
    {
        $num = count($this->objects) + 1;
        $this->objects[$num] = [
            'header' => $header,
            'stream' => $stream,
        ];
    }
}
