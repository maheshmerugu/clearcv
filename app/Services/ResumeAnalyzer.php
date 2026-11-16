<?php

namespace App\Services;

class ResumeAnalyzer
{
    private const STOP_WORDS = [
        'about', 'across', 'after', 'against', 'and', 'are', 'assist', 'based',
        'been', 'being', 'between', 'both', 'build', 'can', 'candidate', 'could',
        'deliver', 'demonstrated', 'develop', 'experience', 'for', 'from', 'have',
        'help', 'into', 'job', 'looking', 'must', 'our', 'over', 'role', 'should',
        'skills', 'team', 'that', 'their', 'this', 'through', 'using', 'will',
        'with', 'work', 'years', 'you',
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

        $this->checkPattern(
            $text,
            '/(?:\+?\d[\d\s().-]{7,}\d)/',
            $findings,
            $deductions,
            'Contact details',
            'A phone number was found',
            'A phone number was not detected',
            'Add a reachable phone number in plain text if you want employers to contact you by phone.',
            10,
        );

        $this->checkSection($text, ['experience', 'work experience', 'employment history', 'work history'], 'Work experience', 15, $findings, $deductions);
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

    private function checkSection(
        string $text,
        array $aliases,
        string $label,
        int $penalty,
        array &$findings,
        int &$deductions,
    ): void {
        $headings = implode('|', array_map(
            fn (string $alias): string => preg_quote($alias, '/'),
            $aliases,
        ));
        $found = preg_match('/^\s*(?:'.$headings.')\s*:?\s*$/im', $text) === 1;

        $this->addFinding(
            $findings,
            $deductions,
            'Sections',
            $found ? 'success' : 'warning',
            $found ? $label.' section detected' : $label.' section not detected',
            $found ? 'A conventional section heading was found in the extracted text.' : 'A conventional heading for this section was not found.',
            $found ? null : 'If this information is relevant to your background, include it under a clearly labeled section.',
            $found ? 0 : $penalty,
        );
    }

    private function analyzeJobMatch(string $text, string $jobDescription, array &$findings, int &$deductions): void
    {
        preg_match_all('/[a-z][a-z0-9+#.-]{2,}/i', mb_strtolower($jobDescription), $matches);
        $words = array_filter(
            $matches[0],
            fn (string $word): bool => ! in_array($word, self::STOP_WORDS, true) && ! is_numeric($word),
        );
        $frequencies = array_count_values($words);
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

        $resumeWords = [];
        preg_match_all('/[a-z][a-z0-9+#.-]{2,}/i', mb_strtolower($text), $resumeMatches);
        $resumeWords = array_unique($resumeMatches[0]);
        $missing = array_values(array_filter($keywords, fn (string $word): bool => ! in_array($word, $resumeWords, true)));
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
                : 'Only add relevant terms when they accurately describe your experience: '.implode(', ', $missing).'.',
            'impact' => $penalty === 0 ? 0 : -$penalty,
        ];
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
