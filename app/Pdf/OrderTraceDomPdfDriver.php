<?php

namespace App\Pdf;

use Spatie\LaravelPdf\Drivers\DomPdfDriver;
use Spatie\LaravelPdf\PdfOptions;

/**
 * Driver DOMPDF específico del reporte Order Trace.
 *
 * Agrega en todas las páginas:
 * - pie institucional del reporte;
 * - paginado real "1 / N" en la esquina inferior derecha.
 *
 * Se hace sobre el canvas de DOMPDF después del render para que el conteo
 * de páginas sea exacto y no dependa de CSS/JavaScript del navegador.
 */
class OrderTraceDomPdfDriver extends DomPdfDriver
{
    public function __construct(
        array $config = [],
        protected string $footerText = ''
    ) {
        parent::__construct($config);
    }

    public function generatePdf(
        string $html,
        ?string $headerHtml,
        ?string $footerHtml,
        PdfOptions $options
    ): string {
        $dompdf = $this->buildDompdf($html, $headerHtml, $footerHtml, $options);
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');

        if ($font) {
            $footerText = $this->footerText;
            $fontSize = 7.0;
            $textColor = [0.0, 0.0, 0.0];
            $lineColor = [0.89, 0.92, 0.95];

            $canvas->page_script(function (
                int $pageNumber,
                int $pageCount,
                $pdf
            ) use ($footerText, $font, $fontSize, $textColor, $lineColor): void {
                $pageWidth = $pdf->get_width();
                $pageHeight = $pdf->get_height();

                // Dentro del margen inferior configurado por el reporte.
                $textY = $pageHeight - 19;
                $lineY = $textY - 7;
                $horizontalMargin = 31;

                $pdf->line(
                    $horizontalMargin,
                    $lineY,
                    $pageWidth - $horizontalMargin,
                    $lineY,
                    $lineColor,
                    0.45
                );

                if ($footerText !== '') {
                    $footerWidth = $pdf->get_text_width($footerText, $font, $fontSize);
                    $footerX = max(
                        $horizontalMargin,
                        ($pageWidth - $footerWidth) / 2
                    );

                    $pdf->text(
                        $footerX,
                        $textY,
                        $footerText,
                        $font,
                        $fontSize,
                        $textColor
                    );
                }

                $pageLabel = $pageNumber . ' / ' . $pageCount;
                $pageLabelWidth = $pdf->get_text_width($pageLabel, $font, $fontSize);

                $pdf->text(
                    $pageWidth - $horizontalMargin - $pageLabelWidth,
                    $textY,
                    $pageLabel,
                    $font,
                    $fontSize,
                    $textColor
                );
            });
        }

        return $dompdf->output();
    }
}
