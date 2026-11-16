<?php

namespace App\Services;

class ResumePdfFormatter
{
    private const HEADINGS = [
        'academic background',
        'awards',
        'certifications',
        'core competencies',
        'career experience',
        'career history',
        'education',
        'employment experience',
        'employment history',
        'experience',
        'interests',
        'languages',
        'licenses',
        'objective',
        'profile',
        'projects',
        'professional experience',
        'professional summary',
        'professional work experience',
        'publications',
        'references',
        'skills',
        'summary',
        'technical skills',
        'volunteer experience',
        'work experience',
        'work history',
    ];

    public function format(string $content): array
    {
        $lines = preg_split('/\R/u', $content) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn (string $line): bool => $line !== ''));
        $lastLineIndex = array_key_last($lines);
        $lines = array_values(array_filter(
            $lines,
            fn (string $line, int $index): bool => ! $this->isPageNumberLine($line)
                && ! (($index === 0 || $index === $lastLineIndex) && preg_match('/^\d{1,2}$/', $line) === 1),
            ARRAY_FILTER_USE_BOTH,
        ));
        $name = array_shift($lines) ?? 'Resume';
        $subtitle = null;

        if (
            $lines !== []
            && ! $this->isContactLine($lines[0])
            && ! $this->isHeading($lines[0])
            && mb_strlen($lines[0]) <= 90
            && preg_match('/[.!?]$/u', $lines[0]) !== 1
        ) {
            $subtitle = array_shift($lines);
        }

        $contact = [];

        while ($lines !== [] && $this->isContactLine($lines[0])) {
            $contact[] = array_shift($lines);
        }

        $sections = [];
        $currentSection = null;

        foreach ($lines as $line) {
            if ($this->isHeading($line)) {
                $sections[] = [
                    'heading' => rtrim($line, ': '),
                    'items' => [],
                ];
                $currentSection = array_key_last($sections);

                continue;
            }

            if ($currentSection === null) {
                $sections[] = ['heading' => null, 'items' => []];
                $currentSection = array_key_last($sections);
            }

            $bullet = preg_match('/^\s*(?:[•▪◦·*?-]|\d+[.)])\s+/u', $line) === 1;
            $itemText = $bullet
                ? (preg_replace('/^\s*(?:[•▪◦·*?-]|\d+[.)])\s+/u', '', $line) ?? $line)
                : $line;

            $sections[$currentSection]['items'][] = [
                'text' => $itemText,
                'bullet' => $bullet,
                'entry' => ! $bullet && preg_match('/\b(?:19|20)\d{2}\b|\s\|\s/', $line) === 1,
            ];
        }

        return [
            'name' => $name,
            'subtitle' => $subtitle,
            'contact' => $contact,
            'sections' => $sections,
        ];
    }

    private function isHeading(string $line): bool
    {
        $heading = mb_strtolower(trim($line, " \t\n\r\0\x0B:"));

        return in_array($heading, self::HEADINGS, true);
    }

    private function isContactLine(string $line): bool
    {
        return preg_match('/@|(?:\+?\d[\d\s().-]{8,}\d)|linkedin|github|https?:\/\/|www\./i', $line) === 1;
    }

    private function isPageNumberLine(string $line): bool
    {
        return preg_match('/^(?:page\s*)?\d{1,3}\s*(?:of|\/)\s*\d{1,3}$|^page\s*\d{1,3}$/i', $line) === 1;
    }
}
