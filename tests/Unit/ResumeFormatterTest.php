<?php

namespace Tests\Unit;

use App\Services\ResumeFormatter;
use PHPUnit\Framework\TestCase;

class ResumeFormatterTest extends TestCase
{
    public function test_it_cleans_whitespace_without_joining_resume_lines(): void
    {
        $formatted = (new ResumeFormatter)->clean("Experience   \r\n\r\n\r\nSkills\t \r\n");

        $this->assertSame("Experience\n\nSkills", $formatted);
    }
}
