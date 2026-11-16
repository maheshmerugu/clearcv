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
}
