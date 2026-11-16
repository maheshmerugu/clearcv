<?php

namespace Tests\Unit;

use App\Services\ResumePdfFormatter;
use PHPUnit\Framework\TestCase;

class ResumePdfFormatterTest extends TestCase
{
    public function test_it_formats_contact_sections_and_bullets_without_losing_text(): void
    {
        $document = (new ResumePdfFormatter)->format(implode("\n", [
            'Taylor Candidate',
            'Software Engineer',
            'taylor@example.com | 555-123-4567',
            'Professional Summary',
            'An experienced engineer.',
            'Work Experience',
            '• Delivered reliable software.',
            'Acme Inc | 2021 - 2025',
        ]));

        $this->assertSame('Taylor Candidate', $document['name']);
        $this->assertSame('Software Engineer', $document['subtitle']);
        $this->assertSame(['taylor@example.com | 555-123-4567'], $document['contact']);
        $this->assertSame('Professional Summary', $document['sections'][0]['heading']);
        $this->assertSame('Delivered reliable software.', $document['sections'][1]['items'][0]['text']);
        $this->assertTrue($document['sections'][1]['items'][0]['bullet']);
        $this->assertSame('Acme Inc | 2021 - 2025', $document['sections'][1]['items'][1]['text']);
        $this->assertTrue($document['sections'][1]['items'][1]['entry']);
    }

    public function test_it_removes_pdf_page_number_lines(): void
    {
        $document = (new ResumePdfFormatter)->format(implode("\n", [
            'Taylor Candidate',
            'Professional Summary',
            'Experienced engineer.',
            'Page 1 of 2',
            '2',
        ]));

        $items = array_column($document['sections'][0]['items'], 'text');

        $this->assertSame(['Experienced engineer.'], $items);
    }
}
