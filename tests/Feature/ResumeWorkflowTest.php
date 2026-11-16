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
        $originalScore = $resume->ats_score;
        $reviewedCopy = str_replace('taylor@example.com', '', $resume->optimized_text);
        $this->actingAs($user)
            ->put(route('resumes.update', $resume), ['optimized_text' => $reviewedCopy])
            ->assertRedirect();
        $this->assertSame($reviewedCopy, $resume->fresh()->optimized_text);
        $this->assertLessThan($originalScore, $resume->fresh()->ats_score);
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
            ->post(route('resumes.tailor', $resume), [
                'confirmed_terms' => ['Laravel'],
                'confirm_skills' => '1',
            ])
            ->assertNotFound();
        $this->actingAs($otherUser)
            ->get(route('resumes.download', $resume))
            ->assertNotFound();
        $this->actingAs($otherUser)
            ->delete(route('resumes.destroy', $resume))
            ->assertNotFound();
    }

    public function test_my_resumes_dashboard_shows_a_delete_action(): void
    {
        $user = User::factory()->create();
        $resume = Resume::create([
            'user_id' => $user->id,
            'original_name' => 'taylor.pdf',
            'original_path' => 'resumes/'.$user->id.'/taylor.pdf',
            'extracted_text' => str_repeat('resume text ', 30),
            'optimized_text' => 'Reviewed resume text',
            'ats_score' => 80,
            'findings' => [],
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('resumes.destroy', $resume))
            ->assertSee('Delete this resume and its report?');
    }

    public function test_dashboard_requires_sign_in(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_report_refreshes_a_stale_score_and_detects_inline_experience_heading(): void
    {
        $user = User::factory()->create();
        $content = 'Taylor Candidate'."\n".'taylor@example.com'."\n".'Summary'."\n"
            .str_repeat('Experienced engineer delivering reliable applications for customers. ', 6)
            ."\nWORK EXPERIENCE: Senior Engineer | Example Co | 2021 - 2025\nEducation\nSkills";
        $resume = Resume::create([
            'user_id' => $user->id,
            'original_name' => 'taylor.pdf',
            'original_path' => 'resumes/'.$user->id.'/taylor.pdf',
            'extracted_text' => $content,
            'optimized_text' => $content,
            'ats_score' => 68,
            'findings' => [],
        ]);

        $this->actingAs($user)->get(route('resumes.show', $resume))->assertOk();

        $this->assertSame(90, $resume->fresh()->ats_score);
        $this->assertContains('Work experience section detected', array_column($resume->fresh()->findings, 'title'));
    }

    public function test_a_user_can_add_only_confirmed_missing_job_terms_to_the_skills_section(): void
    {
        $user = User::factory()->create();
        $content = "Taylor Candidate\ntaylor@example.com\n555-123-4567\nSummary\n"
            .str_repeat('Experienced PHP developer delivering reliable software for customers. ', 6)
            ."\nWork Experience\nSkills\nPHP\nEducation";
        $resume = Resume::create([
            'user_id' => $user->id,
            'original_name' => 'taylor.pdf',
            'original_path' => 'resumes/'.$user->id.'/taylor.pdf',
            'extracted_text' => $content,
            'optimized_text' => $content,
            'job_description' => 'PHP Laravel',
            'ats_score' => 80,
            'findings' => [],
        ]);

        $this->actingAs($user)
            ->post(route('resumes.tailor', $resume), [
                'confirmed_terms' => ['Laravel'],
                'confirm_skills' => '1',
            ])
            ->assertRedirect(route('resumes.show', $resume));

        $updated = $resume->fresh();
        $this->assertStringContainsString("Skills\nPHP\nLaravel", $updated->optimized_text);
        $this->assertSame(100, $updated->ats_score);
        $this->assertSame([], array_values(array_filter(
            $updated->findings,
            fn (array $finding): bool => $finding['category'] === 'Job match' && ! empty($finding['missing_terms']),
        )));
    }

    public function test_a_user_cannot_add_terms_that_are_not_missing_from_their_job_match(): void
    {
        $user = User::factory()->create();
        $resume = Resume::create([
            'user_id' => $user->id,
            'original_name' => 'taylor.pdf',
            'original_path' => 'resumes/'.$user->id.'/taylor.pdf',
            'extracted_text' => str_repeat('PHP developer experience. ', 20),
            'optimized_text' => str_repeat('PHP developer experience. ', 20),
            'job_description' => 'PHP Laravel',
            'ats_score' => 80,
            'findings' => [],
        ]);

        $this->actingAs($user)
            ->from(route('resumes.show', $resume))
            ->post(route('resumes.tailor', $resume), [
                'confirmed_terms' => ['PHP'],
                'confirm_skills' => '1',
            ])
            ->assertSessionHasErrors('confirmed_terms.0');

        $this->assertSame(str_repeat('PHP developer experience. ', 20), $resume->fresh()->optimized_text);
    }
}
