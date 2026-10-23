<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ProgrammePdfController extends Controller
{
    /**
     * The read-only programme of the newest conference as an A4 PDF.
     */
    public function __invoke(): Response
    {
        $conference = Conference::latest('id')->with('days.sessions.items')->firstOrFail();

        $pdf = Pdf::loadView('pdf.programme', ['conference' => $conference])
            ->setPaper('a4')
            ->setOption(['isFontSubsettingEnabled' => true, 'defaultFont' => 'Inter']);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('Inter');
        $grey = [0.36, 0.42, 0.55];
        $y = $canvas->get_height() - 32;
        $canvas->page_text(42, $y, $conference->name.' · Programme', $font, 7.5, $grey);
        $canvas->page_text($canvas->get_width() - 100, $y, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 7.5, $grey);

        $shortName = Str::match('/\(([^)]+)\)$/', $conference->name) ?: $conference->name;

        return $pdf->download(Str::slug($shortName).'-programme.pdf');
    }
}
