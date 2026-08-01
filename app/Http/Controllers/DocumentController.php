<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function stream(Document $record)
    {
        Gate::authorize('view', $record);

        $filename = 'document-'.Str::slug($record->document_number).'.pdf';
        $pdf = Pdf::loadView('documents.pdf', ['record' => $record]);

        return $pdf->stream($filename);
    }
}
