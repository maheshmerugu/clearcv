<?php

namespace App\Http\Controllers;

use App\Services\ResumeAnalyzer;
use App\Services\ResumeFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Smalot\PdfParser\Parser;

class ResumeController extends Controller
{
    public function __construct(
        private readonly ResumeAnalyzer $analyzer,
        private readonly ResumeFormatter $formatter,
    ) {}

    public function dashboard(Request $request): View
    {
        return view('dashboard', [
            'resumes' => $request->user()->resumes()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resume' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'job_description' => ['nullable', 'string', 'max:20000'],
        ]);
        $file = $validated['resume'];

        try {
            $text = trim((new Parser)->parseFile($file->getRealPath())->getText());
        } catch (\Exception $exception) {
            Log::warning('Resume PDF parsing failed.', [
                'user_id' => $request->user()->id,
                'exception' => $exception->getMessage(),
            ]);

            return back()
                ->withInput($request->except('resume'))
                ->withErrors(['resume' => 'We could not read this PDF. Please upload a valid, unencrypted, text-based PDF.']);
        }

        if (mb_strlen(preg_replace('/\s+/', '', $text) ?? '') < 50) {
            return back()
                ->withInput($request->except('resume'))
                ->withErrors(['resume' => 'This PDF contains too little readable text. Try exporting a text-based PDF instead of a scan.']);
        }

        $fileName = Str::uuid().'.pdf';
        $path = $file->storeAs('resumes/'.$request->user()->id, $fileName, 'local');

        if ($path === false) {
            Log::error('Resume PDF could not be saved to private storage.', ['user_id' => $request->user()->id]);

            return back()->withErrors(['resume' => 'The resume could not be saved. Please try again.']);
        }

        $cleanText = $this->formatter->clean($text);
        $analysis = $this->analyzer->analyze($text, $validated['job_description'] ?? null);

        try {
            $resume = $request->user()->resumes()->create([
                'original_name' => $file->getClientOriginalName(),
                'original_path' => $path,
                'extracted_text' => $text,
                'optimized_text' => $cleanText,
                'job_description' => $validated['job_description'] ?? null,
                'ats_score' => $analysis['score'],
                'findings' => $analysis['findings'],
            ]);
        } catch (QueryException $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        return redirect()->route('resumes.show', $resume)->with('status', 'Your resume has been checked. Review the suggestions and edit the clean copy before downloading.');
    }

    public function show(Request $request, int $resume): View
    {
        $resume = $request->user()->resumes()->findOrFail($resume);

        return view('resumes.show', compact('resume'));
    }

    public function update(Request $request, int $resume): RedirectResponse
    {
        $resume = $request->user()->resumes()->findOrFail($resume);
        $validated = $request->validate([
            'optimized_text' => ['required', 'string', 'max:100000'],
        ]);
        $content = trim(str_replace(["\r\n", "\r"], "\n", $validated['optimized_text']));

        if ($content === '') {
            return back()->withErrors(['optimized_text' => 'The optimized resume cannot be empty.']);
        }

        $resume->update([
            'optimized_text' => $content,
        ]);

        return back()->with('status', 'Your reviewed resume copy has been saved. Download the PDF when you are ready.');
    }

    public function download(Request $request, int $resume)
    {
        $resume = $request->user()->resumes()->findOrFail($resume);

        if (! filled($resume->optimized_text)) {
            return redirect()->route('resumes.show', $resume)->withErrors([
                'optimized_text' => 'Save a reviewed resume copy before downloading.',
            ]);
        }

        return Pdf::loadView('resumes.pdf', ['resume' => $resume])
            ->setPaper('a4')
            ->download(Str::slug(pathinfo($resume->original_name, PATHINFO_FILENAME)).'-optimized.pdf');
    }

    public function destroy(Request $request, int $resume): RedirectResponse
    {
        $resume = $request->user()->resumes()->findOrFail($resume);
        Storage::disk('local')->delete($resume->original_path);
        $resume->delete();

        return redirect()->route('dashboard')->with('status', 'The resume and its saved analysis were deleted.');
    }
}
