# ATS Resume Checker & Resume Optimizer

A Laravel web application for reviewing a resume's readability and relevance to applicant tracking systems (ATS), explaining potential issues, and helping users prepare an improved, downloadable resume.

> **Score disclaimer:** The application can help users work toward a 100/100 score against its own checks. It cannot guarantee that an employer's ATS will assign the same score. ATS products and employer configurations use different criteria, and a score is guidance—not a hiring outcome.

## Features

- Account registration, sign-in, sign-out, and a private resume dashboard.
- PDF upload with file validation and text extraction. Files are stored on Laravel's private local disk.
- Explainable checks for extracted text, contact details, common resume sections, and optional job-description terms.
- An editable, whitespace-cleaned copy that preserves the extracted content, recalculates the score after edits, and leaves substantive changes under the user's control.
- A job-tailoring action that lets users select missing job-description terms and confirm they genuinely have those skills before adding them to the Skills section and recalculating the score.
- A profile review form for signed-in users; each user can publish one star-rated review, edit it, or remove it. Published reviews appear on the home page.
- Download of the reviewed copy as a clean, text-based PDF without generated page numbers, plus report history and deletion.
- Owner-scoped access to resume records, reports, and downloads.

## User workflow

1. **Create an account or sign in.** Users register with their name, email, and password, then access their private workspace.
2. **Upload a resume.** The user submits a PDF. The application validates the file type and size, stores it privately, and extracts readable text for analysis.
3. **Review the ATS check.** The application evaluates extracted text, contact details, common resume sections, and—if supplied—relevance to selected terms in a target job description.
4. **Understand the results.** A report presents an overall score, category-level findings, detected strengths, and actionable issues. Each suggestion should explain what to change and why.
5. **Improve the resume.** Users can edit the copy directly or select missing job terms they confirm are accurate skills before adding them to the Skills section. The application does not invent experience, qualifications, or achievements; users should leave inaccurate terms unselected and review all changes.
6. **Download and verify.** The user downloads an ATS-friendly PDF and reviews it before applying. The original resume remains unchanged unless the user explicitly chooses to replace it.

### Analysis and score guidance

The score should be calculated from checks the application can explain, rather than presented as a prediction of a specific employer's hiring system. The current text-based heuristic checks:

- Whether at least a minimal amount of text can be extracted from the PDF.
- Whether an email, phone number, and conventional experience, education, skills, and summary headings appear in the extracted text.
- Up to 12 frequent, non-generic terms from an optional job description.

The “Matched X of Y” result is only the optional job-keyword comparison; it is not the overall resume score. Saving edits reruns the checks against the saved copy. The tailoring action accepts only currently missing terms and requires the user to confirm they genuinely have the selected skills. A perfect score is not forced, and a high score cannot guarantee an employer's result.

The checker does not inspect visual layout, diagnose every parsing issue, or use an employer's ATS. The optimized copy starts as a whitespace-cleaned text version; users remain responsible for reviewing direct edits and every confirmed skill before downloading.

Different employers and ATS vendors can interpret the same resume differently. A high score is not a guarantee of successful parsing, ranking, interview selection, or employment.

## Product principles

- **Authentication and ownership:** Resume documents, reports, and downloads are accessible only to their owner.
- **PDF handling:** Accept text-based PDFs up to 10 MB, report unreadable or unsupported documents clearly, and keep source files in private storage.
- **Transparent results:** Show score components and actionable recommendations instead of relying on an unexplained number.
- **Human review:** Let users inspect proposed changes and the exported PDF before using it.
- **Privacy:** Uploaded files and reports stay in the user's account until they delete them. Resume contents are not sent to an external analysis service.
- **Public reviews:** Review text and the display name chosen by the author are public on the home page immediately after submission. Users can edit or remove their review from their profile.
- **Accessible, responsive interface:** Support keyboard navigation, clear validation messages, and common desktop and mobile screen sizes.

## Technology

- PHP 8.2+
- Laravel 12
- MySQL by default (database connection is configurable through Laravel environment settings)
- `smalot/pdfparser` for PDF text extraction
- `barryvdh/laravel-dompdf` for PDF export
- Vite, Tailwind CSS 4, and JavaScript for frontend assets

## Local development

Create the application database in MySQL (for example, through phpMyAdmin or the MySQL command line):

```sql
CREATE DATABASE atos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

The default local configuration targets a XAMPP MySQL server at `127.0.0.1:3306` with database `atos`, username `root`, and no password. Update the `DB_*` values in `.env` if your MySQL credentials or database name differ. Ensure the MySQL service is running and PHP's `pdo_mysql` extension is enabled.

From the project directory in PowerShell:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Open the URL printed by `php artisan serve` (usually `http://127.0.0.1:8000`). For frontend hot reload during development, run `npm run dev` in a second terminal instead of using the production asset build alone.

If PHP, Composer, or Node.js is not on `PATH`, run the commands using the executable paths configured for your XAMPP/development environment. Do not commit `.env` or other credentials.

### Useful commands

```powershell
php artisan test
vendor\bin\pint --test
npm run build
```

## Application structure

- `routes/web.php` — public landing page and authenticated product routes.
- `app/Http/Controllers/AuthController.php` — registration and session authentication.
- `app/Http/Controllers/ResumeController.php` — upload, report, edit, download, and deletion workflow.
- `app/Services/ResumeAnalyzer.php` — explainable text and job-description checks.
- `app/Models/Resume.php` and `database/migrations/` — user-owned resume records and report findings.
- `resources/views/` — responsive landing, account, dashboard, report, and PDF templates.
- `resources/css/` and `resources/js/` — Vite frontend entry points.

## Development roadmap

1. Add OCR support for scanned PDFs and more detailed layout checks.
2. Improve role-specific analysis while keeping every score component explainable.
3. Add stronger resume editing tools without changing or inventing user facts.
4. Expand test coverage for malformed uploads, validation limits, and PDF edge cases.
"# clearcv" 
