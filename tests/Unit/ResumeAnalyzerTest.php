<?php

namespace Tests\Unit;

use App\Services\ResumeAnalyzer;
use PHPUnit\Framework\TestCase;

class ResumeAnalyzerTest extends TestCase
{
    public function test_it_reports_missing_sections_and_job_terms_without_inventing_content(): void
    {
        $analysis = (new ResumeAnalyzer)->analyze(
            "Taylor Candidate\n".str_repeat('Experienced team member with strong communication and leadership experience. ', 5),
            'Senior PHP engineer with Laravel and MySQL experience.',
        );

        $this->assertLessThan(100, $analysis['score']);
        $this->assertContains('Job match', array_column($analysis['findings'], 'category'));
        $this->assertContains(
            'Education section not detected',
            array_column($analysis['findings'], 'title'),
        );
    }

    public function test_it_detects_experience_headings_with_variations_and_inline_details(): void
    {
        $text = str_repeat('Experienced engineer delivering customer-focused software and measurable improvements. ', 5)
            ."\nWORK EXPERIENCE: Senior Engineer | Example Co | 2021 - 2025";

        $analysis = (new ResumeAnalyzer)->analyze($text);

        $this->assertContains('Work experience section detected', array_column($analysis['findings'], 'title'));
    }

    public function test_job_match_ignores_boilerplate_and_matches_common_word_variations(): void
    {
        $analysis = (new ResumeAnalyzer)->analyze(
            'Project manager with PHP and C# engineer.',
            'Candidates are required to have strong project management skills, PHP, C#, and engineering experience.',
        );
        $jobMatch = array_values(array_filter(
            $analysis['findings'],
            fn (array $finding): bool => $finding['category'] === 'Job match',
        ))[0];

        $this->assertSame('Matched 5 of 5 role-related terms', $jobMatch['title']);
    }

    public function test_job_match_ignores_generic_phrases_and_splits_hyphenated_terms(): void
    {
        $analysis = (new ResumeAnalyzer)->analyze(
            'Software engineer building web applications with PHP and Laravel.',
            'We want a responsible engineer creating an array of web-based applications using PHP and Laravel.',
        );
        $jobMatch = array_values(array_filter(
            $analysis['findings'],
            fn (array $finding): bool => $finding['category'] === 'Job match',
        ))[0];

        $this->assertSame('Matched 5 of 5 role-related terms', $jobMatch['title']);
    }

    public function test_job_match_normalizes_backend_terms_and_ignores_level_boilerplate(): void
    {
        $analysis = (new ResumeAnalyzer)->analyze(
            'Backend engineer building web applications.',
            'Backend engineer for web-based applications at a senior level.',
        );
        $jobMatch = array_values(array_filter(
            $analysis['findings'],
            fn (array $finding): bool => $finding['category'] === 'Job match',
        ))[0];

        $this->assertSame('Matched 4 of 5 role-related terms', $jobMatch['title']);
    }
}
