<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResumeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_signed_in_user_can_upload_review_and_download_a_resume(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $resumeText = implode("\n", [
            'Taylor Candidate',
            'taylor@example.com | 555-123-4567',
            'Professional Summary',
            str_repeat('Experienced product engineer delivering reliable software and collaborating with cross-functional teams. ', 3),
            'Work Experience',
            'Skills',
            'Education',
        ]);
        $pdfContents = Pdf::loadHTML('<html><body><pre>'.$resumeText.'</pre></body></html>')->output();

        $response = $this->actingAs($user)->post(route('resumes.store'), [
            'resume' => UploadedFile::fake()->createWithContent('taylor-resume.pdf', $pdfContents),
            'job_description' => 'Product engineer with software delivery and collaboration experience.',
        ]);

        $resume = Resume::firstOrFail();
        $response->assertRedirect(route('resumes.show', $resume));
        $this->assertSame($user->id, $resume->user_id);
        $this->assertNotEmpty($resume->extracted_text);
        $this->assertNotEmpty($resume->findings);
        Storage::disk('local')->assertExists($resume->original_path);

        $this->actingAs($user)->get(route('resumes.show', $resume))->assertOk();
        $reviewedCopy = $resume->optimized_text."\nReviewed by Taylor.";
        $this->actingAs($user)
            ->put(route('resumes.update', $resume), ['optimized_text' => $reviewedCopy])
            ->assertRedirect();
        $this->assertSame($reviewedCopy, $resume->fresh()->optimized_text);
        $this->actingAs($user)->get(route('resumes.download', $resume))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->delete(route('resumes.destroy', $resume))
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('resumes', ['id' => $resume->id]);
        Storage::disk('local')->assertMissing($resume->original_path);
    }

    public function test_a_user_cannot_view_another_users_resume(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $resume = Resume::create([
            'user_id' => $owner->id,
            'original_name' => 'private.pdf',
            'original_path' => 'resumes/'.$owner->id.'/private.pdf',
            'extracted_text' => str_repeat('resume text ', 30),
            'optimized_text' => 'Reviewed resume text',
            'ats_score' => 80,
            'findings' => [],
        ]);

        $this->actingAs($otherUser)
            ->get(route('resumes.show', $resume))
            ->assertNotFound();
        $this->actingAs($otherUser)
            ->get(route('resumes.download', $resume))
            ->assertNotFound();
    }

    public function test_dashboard_requires_sign_in(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
