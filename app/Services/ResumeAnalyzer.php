<?php

namespace App\Services;

class ResumeAnalyzer
{
    private const STOP_WORDS = [
        'ability', 'about', 'across', 'after', 'against', 'also', 'and', 'are',
        'assist', 'back', 'based', 'been', 'being', 'between', 'both', 'build', 'can',
        'candidate', 'company', 'could', 'create', 'creation', 'deliver',
        'demonstrated', 'excellent', 'experience', 'for', 'from', 'have', 'help', 'ideal', 'include',
        'end', 'including', 'into', 'job', 'level', 'looking', 'must', 'of', 'opportunity',
        'our', 'over', 'prefer', 'position', 'provide', 'qualified', 'qualification',
        'requir', 'requirement', 'requirements', 'responsibilities', 'responsibility',
        'responsible', 'role', 'seeking', 'should', 'skill', 'strong', 'successful', 'team',
        'that', 'the', 'their', 'this', 'through', 'using', 'various', 'will',
        'want', 'with', 'work', 'year', 'years', 'you',
    ];

    public function analyze(string $text, ?string $jobDescription = null): array
    {
        $findings = [];
        $deductions = 0;

        if (mb_strlen(preg_replace('/\s+/', '', $text) ?? '') < 300) {
            $this->addFinding(
                $findings,
                $deductions,
                'Text parsing',
                'warning',
                'Limited text was extracted',
                'The resume has relatively little readable text. This can happen with image-only or highly designed PDFs.',
                'Use a text-based PDF and confirm that the exported text contains the complete resume.',
                10,
            );
        } else {
            $this->addFinding(
                $findings,
                $deductions,
                'Text parsing',
                'success',
                'Readable text was extracted',
                'The PDF contains enough extractable text for this review.',
                null,
                0,
            );
        }

        $this->checkPattern(
            $text,
            '/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i',
            $findings,
            $deductions,
            'Contact details',
            'An email address was found',
            'No email address was detected',
            'Add a professional email address in plain text near the top of the resume.',
            15,
        );

        $this->checkPhoneNumber($text, $findings, $deductions);

        $this->checkSection($text, [
            'experience',
            'work experience',
            'professional experience',
            'professional work experience',
            'employment experience',
            'employment history',
            'career experience',
            'career history',
            'work history',
        ], 'Work experience', 15, $findings, $deductions);
        $this->checkSection($text, ['education', 'academic background'], 'Education', 10, $findings, $deductions);
        $this->checkSection($text, ['skills', 'technical skills', 'core competencies'], 'Skills', 10, $findings, $deductions);
        $this->checkSection($text, ['summary', 'professional summary', 'profile', 'objective'], 'Summary or profile', 5, $findings, $deductions);

        if (filled($jobDescription)) {
            $this->analyzeJobMatch($text, $jobDescription, $findings, $deductions);
        } else {
            $findings[] = [
                'category' => 'Job match',
                'severity' => 'info',
                'title' => 'No job description provided',
                'message' => 'This score does not include role-specific keyword relevance.',
                'recommendation' => 'Add a target job description when you want a role-specific comparison.',
                'impact' => 0,
            ];
        }

        return [
            'score' => max(0, 100 - $deductions),
            'findings' => $findings,
        ];
    }

    private function checkPattern(
        string $text,
        string $pattern,
        array &$findings,
        int &$deductions,
        string $category,
        string $successTitle,
        string $warningTitle,
        string $recommendation,
        int $penalty,
    ): void {
        $found = preg_match($pattern, $text) === 1;
        $this->addFinding(
            $findings,
            $deductions,
            $category,
            $found ? 'success' : 'warning',
            $found ? $successTitle : $warningTitle,
            $found ? 'This common resume detail appears in the extracted text.' : 'No matching detail was found in the extracted text.',
            $found ? null : $recommendation,
            $found ? 0 : $penalty,
        );
    }

    private function checkPhoneNumber(string $text, array &$findings, int &$deductions): void
    {
        preg_match_all('/\+?\d[\d\s().-]{6,}\d/', $text, $matches);
        $found = false;

        foreach ($matches[0] as $match) {
            $digits = preg_replace('/\D/', '', $match) ?? '';

            if (strlen($digits) >= 10 && strlen($digits) <= 15) {
                $found = true;

                break;
            }
        }

        $this->addFinding(
            $findings,
            $deductions,
            'Contact details',
            $found ? 'success' : 'warning',
            $found ? 'A phone number was found' : 'A phone number was not detected',
            $found ? 'This common resume detail appears in the extracted text.' : 'No matching phone number was found in the extracted text.',
            $found ? null : 'Add a reachable phone number in plain text if you want employers to contact you by phone.',
            $found ? 0 : 10,
        );
    }

    private function checkSection(
        string $text,
        array $aliases,
        string $label,
        int $penalty,
        array &$findings,
        int &$deductions,
    ): void {
        $found = false;

        foreach ($aliases as $alias) {
            $heading = preg_quote($alias, '/');
            $suffix = $alias === 'experience'
                ? '[ \t]*:?[ \t]*'
                : '(?:[ \t]*[:\-–—|][ \t]*.*|[ \t]+.*)?';

            if (preg_match('/^[ \t]*(?:[-•*][ \t]*)?'.$heading.$suffix.'$/imu', $text) === 1) {
                $found = true;

                break;
            }
        }

        $this->addFinding(
            $findings,
            $deductions,
            'Sections',
            $found ? 'success' : 'warning',
            $found ? $label.' section detected' : $label.' section not detected',
            $found ? 'A conventional section heading was found in the extracted text.' : 'A conventional heading for this section was not found.',
            $found ? null : $this->sectionRecommendation($label),
            $found ? 0 : $penalty,
        );
    }

    private function sectionRecommendation(string $label): string
    {
        return match ($label) {
            'Work experience' => "Use a clear heading such as 'Work Experience' or 'Professional Experience' above your relevant roles.",
            'Education' => "Use a clear 'Education' heading above your degrees, schools, or relevant training.",
            'Skills' => "Use a clear 'Skills' heading above skills you can accurately demonstrate.",
            default => "If relevant, add a short summary under a clearly labeled 'Summary' or 'Profile' heading.",
        };
    }

    private function analyzeJobMatch(string $text, string $jobDescription, array &$findings, int &$deductions): void
    {
        $frequencies = [];
        $displayTerms = [];

        foreach ($this->extractTerms($jobDescription) as $term) {
            $normalized = $this->normalizeTerm($term);

            if ($normalized === '' || in_array($normalized, self::STOP_WORDS, true)) {
                continue;
            }

            $frequencies[$normalized] = ($frequencies[$normalized] ?? 0) + 1;
            $displayTerms[$normalized] ??= $term;
        }

        arsort($frequencies);
        $keywords = array_slice(array_keys($frequencies), 0, 12);

        if ($keywords === []) {
            $findings[] = [
                'category' => 'Job match',
                'severity' => 'info',
                'title' => 'No role keywords identified',
                'message' => 'The supplied job description did not contain enough distinct terms for comparison.',
                'recommendation' => null,
                'impact' => 0,
            ];

            return;
        }

        $resumeWords = array_unique(array_map(
            fn (string $term): string => $this->normalizeTerm($term),
            $this->extractTerms($text),
        ));
        $missing = array_values(array_filter($keywords, fn (string $word): bool => ! in_array($word, $resumeWords, true)));
        $missingDisplayTerms = array_map(fn (string $term): string => $displayTerms[$term], $missing);
        $matched = count($keywords) - count($missing);
        $penalty = (int) round((count($missing) / count($keywords)) * 25);
        $deductions += $penalty;

        $findings[] = [
            'category' => 'Job match',
            'severity' => $missing === [] ? 'success' : 'warning',
            'title' => 'Matched '.$matched.' of '.count($keywords).' role-related terms',
            'message' => $missing === []
                ? 'The selected terms from the job description also appear in the resume.'
                : 'Some terms from the job description were not found in the extracted resume text.',
            'recommendation' => $missing === []
                ? null
                : 'Only add relevant terms when they accurately describe your experience: '.implode(', ', $missingDisplayTerms).'.',
            'impact' => $penalty === 0 ? 0 : -$penalty,
            'missing_terms' => $missingDisplayTerms,
        ];
    }

    private function extractTerms(string $text): array
    {
        $text = preg_replace('/\b(?:an?\s+)?array\s+of\b/i', ' ', $text) ?? $text;
        $text = preg_replace('/\bback[\s-]?end\b/i', 'backend', $text) ?? $text;
        $text = preg_replace('/\bfront[\s-]?end\b/i', 'frontend', $text) ?? $text;
        $text = preg_replace('/\bfull[\s-]?stack\b/i', 'fullstack', $text) ?? $text;
        $text = preg_replace('/\bweb[\s-]?based\b/i', 'webbased', $text) ?? $text;
        preg_match_all(
            '/(?<![A-Za-z0-9])(?:C\+\+|C#|[A-Z]{2,5}|[A-Za-z][A-Za-z0-9+#.]{2,})(?![A-Za-z0-9])/u',
            $text,
            $matches,
        );

        return $matches[0];
    }

    private function normalizeTerm(string $term): string
    {
        $term = mb_strtolower(trim($term, '.-'));

        if (in_array($term, ['c#', 'c++'], true)) {
            return $term;
        }

        if ($term === 'webbased') {
            return 'web';
        }

        if (strlen($term) > 5 && str_ends_with($term, 'ies')) {
            $term = substr($term, 0, -3).'y';
        } elseif (strlen($term) > 4 && str_ends_with($term, 's') && ! str_ends_with($term, 'ss')) {
            $term = substr($term, 0, -1);
        }

        if (strlen($term) > 6 && str_ends_with($term, 'ing')) {
            $term = substr($term, 0, -3);
            $term = preg_replace('/([b-df-hj-np-tv-z])\1$/', '$1', $term) ?? $term;
            $term = preg_match('/(?:at|ag|iz)$/', $term) === 1 ? $term.'e' : $term;
        } elseif (strlen($term) > 5 && str_ends_with($term, 'ed')) {
            $term = substr($term, 0, -2);
            $term = preg_replace('/([b-df-hj-np-tv-z])\1$/', '$1', $term) ?? $term;
            $term = preg_match('/(?:at|ag|iz)$/', $term) === 1 ? $term.'e' : $term;
        }

        if (strlen($term) > 7 && str_ends_with($term, 'ment')) {
            $term = substr($term, 0, -4);
        } elseif (strlen($term) > 6 && str_ends_with($term, 'er') && ! str_ends_with($term, 'eer')) {
            $term = substr($term, 0, -2);
            $term = str_ends_with($term, 'ag') ? $term.'e' : $term;
        }

        return $term;
    }

    private function addFinding(
        array &$findings,
        int &$deductions,
        string $category,
        string $severity,
        string $title,
        string $message,
        ?string $recommendation,
        int $penalty,
    ): void {
        $deductions += $penalty;
        $findings[] = [
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'recommendation' => $recommendation,
            'impact' => $penalty === 0 ? 0 : -$penalty,
        ];
    }
}
