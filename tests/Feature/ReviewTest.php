<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_displays_published_reviews_with_star_ratings(): void
    {
        $user = User::factory()->create();
        $user->review()->create([
            'display_name' => 'Taylor',
            'rating' => 4,
            'content' => 'The suggestions helped me improve my resume.',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Taylor')
            ->assertSee('The suggestions helped me improve my resume.')
            ->assertSee('4 out of 5 stars')
            ->assertSee('4.0 out of 5');
    }

    public function test_a_signed_in_user_can_publish_edit_and_remove_their_review(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Share your ClearCV review');

        $this->post(route('profile.review.store'), [
            'display_name' => 'Taylor',
            'rating' => 5,
            'content' => 'A useful way to check a resume against a role.',
        ])->assertRedirect(route('profile'));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'display_name' => 'Taylor',
            'rating' => 5,
        ]);
        $this->get(route('home'))->assertSee('A useful way to check a resume against a role.');

        $this->post(route('profile.review.store'), [
            'display_name' => 'Taylor C.',
            'rating' => 4,
            'content' => 'The keyword suggestions were helpful and clear.',
        ])->assertRedirect(route('profile'));

        $this->assertSame(1, Review::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'display_name' => 'Taylor C.',
            'rating' => 4,
        ]);

        $this->delete(route('profile.review.destroy'))->assertRedirect(route('profile'));
        $this->assertDatabaseMissing('reviews', ['user_id' => $user->id]);
    }

    public function test_review_profile_requires_authentication_and_valid_review_fields(): void
    {
        $this->get(route('profile'))->assertRedirect(route('login'));
        $this->post(route('profile.review.store'), [
            'display_name' => 'Guest',
            'rating' => 6,
            'content' => 'Short',
        ])->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('profile.review.store'), [
                'display_name' => 'Taylor',
                'rating' => 6,
                'content' => 'Too short',
            ])
            ->assertSessionHasErrors(['rating', 'content']);

        $this->assertDatabaseCount('reviews', 0);
    }
}
