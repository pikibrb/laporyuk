<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class FeedbackSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_page_is_open_to_guests(): void
    {
        $this->get(route('feedback'))
            ->assertOk()
            ->assertSee('Masukan &amp; Saran', false);
    }

    public function test_guest_can_submit_anonymous_feedback(): void
    {
        Volt::test('feedback-form')
            ->set('type', Feedback::TYPE_SUGGESTION)
            ->set('message', 'Mohon tambahkan filter berdasarkan kecamatan.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertDispatched('notify', type: 'success');

        $feedback = Feedback::query()->firstOrFail();

        $this->assertSame(Feedback::TYPE_SUGGESTION, $feedback->type);
        $this->assertNull($feedback->user_id);
        $this->assertNull($feedback->name);
        $this->assertNull($feedback->contact);
    }

    public function test_authenticated_feedback_is_linked_to_the_user_and_keeps_optional_contact(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('feedback-form')
            ->set('type', Feedback::TYPE_APPRECIATION)
            ->set('message', 'Tampilan peta sangat membantu warga di lingkungan saya.')
            ->set('name', $user->name)
            ->set('contact', $user->email)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('feedback', [
            'user_id' => $user->id,
            'type' => Feedback::TYPE_APPRECIATION,
            'contact' => $user->email,
        ]);
    }

    public function test_feedback_message_is_required_with_a_minimum_length(): void
    {
        Volt::test('feedback-form')
            ->set('message', 'pendek')
            ->call('submit')
            ->assertHasErrors(['message']);

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_feedback_type_must_be_a_known_option(): void
    {
        Volt::test('feedback-form')
            ->set('type', 'tidak-valid')
            ->set('message', 'Masukan dengan jenis yang tidak dikenal.')
            ->call('submit')
            ->assertHasErrors(['type']);

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_blank_name_and_contact_are_stored_as_null(): void
    {
        Volt::test('feedback-form')
            ->set('message', 'Saran untuk mempercepat proses verifikasi laporan.')
            ->set('name', '')
            ->set('contact', '')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('feedback', [
            'name' => null,
            'contact' => null,
        ]);
    }
}
