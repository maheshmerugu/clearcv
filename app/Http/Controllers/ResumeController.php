<?php

namespace App\Http\Controllers;

use App\Services\ResumeAnalyzer;
use App\Services\ResumeFormatter;
use App\Services\ResumePdfFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Smalot\PdfParser\Parser;

class ResumeController extends Controller
{
    public function __construct(
        private readonly ResumeAnalyzer $analyzer,
        private readonly ResumeFormatter $formatter,
        private readonly ResumePdfFormatter $pdfFormatter,
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
        $analysis = $this->analyzer->analyze($resume->optimized_text, $resume->job_description);

        if ($resume->ats_score !== $analysis['score'] || $resume->findings !== $analysis['findings']) {
            $resume->update([
                'ats_score' => $analysis['score'],
                'findings' => $analysis['findings'],
            ]);
        }

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

        $analysis = $this->analyzer->analyze($content, $resume->job_description);
        $resume->update([
            'optimized_text' => $content,
            'ats_score' => $analysis['score'],
            'findings' => $analysis['findings'],
        ]);

        return back()->with('status', 'Your reviewed copy has been saved and its score recalculated. Review the updated findings before downloading.');
    }

    public function tailor(Request $request, int $resume): RedirectResponse
    {
        $resume = $request->user()->resumes()->findOrFail($resume);
        $analysis = $this->analyzer->analyze($resume->optimized_text, $resume->job_description);
        $jobMatch = collect($analysis['findings'])->firstWhere('category', 'Job match');
        $missingTerms = $jobMatch['missing_terms'] ?? [];

        $validated = $request->validate([
            'confirmed_terms' => ['required', 'array', 'min:1', 'max:12'],
            'confirmed_terms.*' => ['required', 'string', 'distinct', 'max:80', Rule::in($missingTerms)],
            'confirm_skills' => ['accepted'],
        ]);

        $content = $this->addConfirmedSkills($resume->optimized_text, $validated['confirmed_terms']);
        $updatedAnalysis = $this->analyzer->analyze($content, $resume->job_description);
        $resume->update([
            'optimized_text' => $content,
            'ats_score' => $updatedAnalysis['score'],
            'findings' => $updatedAnalysis['findings'],
        ]);

        return redirect()->route('resumes.show', $resume)
            ->with('status', 'Your confirmed terms were added to Skills and the score was recalculated. Review the wording before downloading.');
    }

    public function download(Request $request, int $resume)
    {
        $resume = $request->user()->resumes()->findOrFail($resume);

        if (! filled($resume->optimized_text)) {
            return redirect()->route('resumes.show', $resume)->withErrors([
                'optimized_text' => 'Save a reviewed resume copy before downloading.',
            ]);
        }

        return Pdf::loadView('resumes.pdf', [
            'resume' => $resume,
            'document' => $this->pdfFormatter->format($resume->optimized_text),
        ])
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

    /**
     * Add user-confirmed terms to the existing Skills section, or create one if absent.
     */
    private function addConfirmedSkills(string $content, array $terms): string
    {
        $lines = explode("\n", $content);
        $skillsHeading = '/^[ \t]*(?:skills|technical skills|core competencies)(?:[ \t]*[:\-–—|][ \t]*.*)?[ \t]*$/iu';
        $sectionHeading = '/^[ \t]*(?:summary|professional summary|profile|objective|experience|work experience|professional experience|professional work experience|employment experience|employment history|career experience|career history|work history|education|projects|certifications|licenses|awards|publications|volunteer experience|skills|technical skills|core competencies)(?:[ \t]*[:\-–—|][ \t]*.*)?[ \t]*$/iu';
        $skillsIndex = null;
        $sectionEnd = count($lines);

        foreach ($lines as $index => $line) {
            if (preg_match($skillsHeading, $line) === 1) {
                $skillsIndex = $index;
                break;
            }
        }

        if ($skillsIndex === null) {
            $lines[] = '';
            $lines[] = 'Skills';
            $lines[] = implode(', ', $terms);

            return implode("\n", $lines);
        }

        for ($index = $skillsIndex + 1; $index < count($lines); $index++) {
            if (preg_match($sectionHeading, $lines[$index]) === 1) {
                $sectionEnd = $index;
                break;
            }
        }

        $existingSkills = implode("\n", array_slice($lines, $skillsIndex + 1, $sectionEnd - $skillsIndex - 1));
        $newTerms = array_values(array_filter(
            $terms,
            fn (string $term): bool => stripos($existingSkills, $term) === false,
        ));

        if ($newTerms !== []) {
            array_splice($lines, $sectionEnd, 0, [implode(', ', $newTerms)]);
        }

        return implode("\n", $lines);
    }
}
