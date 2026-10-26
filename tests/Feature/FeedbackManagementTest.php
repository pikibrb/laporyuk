<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Volt\Volt;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FeedbackManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('masukan.index'))->assertRedirect(route('login'));
    }

    public function test_citizen_without_admin_role_cannot_access_the_feedback_inbox(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('masukan.index'))->assertForbidden();
    }

    public function test_admin_can_read_feedback_from_citizens(): void
    {
        Feedback::factory()->create([
            'type' => Feedback::TYPE_SUGGESTION,
            'message' => 'Mohon tambahkan filter laporan per kecamatan.',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('masukan.index'))
            ->assertOk()
            ->assertSee('Kotak Masukan Warga')
            ->assertSee('Mohon tambahkan filter laporan per kecamatan.')
            ->assertSee('Menunggu tanggapan');
    }

    public function test_admin_can_respond_to_feedback(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $feedback = Feedback::factory()->create(['message' => 'Peta perlu penanda lokasi rawan banjir.']);

        $this->actingAs($admin);

        Volt::test('feedback-admin')
            ->call('startResponding', $feedback->id)
            ->set('response', 'Penanda lokasi rawan banjir sedang kami siapkan.')
            ->call('saveResponse')
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'success')
            ->assertSet('respondingId', null);

        $feedback->refresh();

        $this->assertSame('Penanda lokasi rawan banjir sedang kami siapkan.', $feedback->response);
        $this->assertSame($admin->id, $feedback->responded_by);
        $this->assertNotNull($feedback->responded_at);
        $this->assertTrue($feedback->isResponded());
    }

    public function test_response_is_required(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $feedback = Feedback::factory()->create();

        $this->actingAs($admin);

        Volt::test('feedback-admin')
            ->call('startResponding', $feedback->id)
            ->set('response', '')
            ->call('saveResponse')
            ->assertHasErrors(['response']);

        $this->assertNull($feedback->fresh()->response);
        $this->assertNull($feedback->fresh()->responded_at);
    }

    public function test_admin_can_update_an_existing_response(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $feedback = Feedback::factory()->create([
            'response' => 'Tanggapan lama.',
            'responded_by' => $admin->id,
            'responded_at' => now()->subDay(),
        ]);

        $this->actingAs($admin);

        Volt::test('feedback-admin')
            ->call('startResponding', $feedback->id)
            ->assertSet('response', 'Tanggapan lama.')
            ->set('response', 'Tanggapan yang sudah diperbarui.')
            ->call('saveResponse')
            ->assertHasNoErrors();

        $this->assertSame('Tanggapan yang sudah diperbarui.', $feedback->fresh()->response);
    }

    public function test_admin_can_filter_feedback_that_still_needs_a_response(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Feedback::factory()->create([
            'message' => 'Masukan yang belum ditanggapi.',
        ]);

        Feedback::factory()->create([
            'message' => 'Masukan yang sudah ditanggapi.',
            'response' => 'Sudah kami tindak lanjuti.',
            'responded_by' => $admin->id,
            'responded_at' => now(),
        ]);

        $this->actingAs($admin);

        Volt::test('feedback-admin')
            ->set('filter', 'pending')
            ->assertSee('Masukan yang belum ditanggapi.')
            ->assertDontSee('Masukan yang sudah ditanggapi.');
    }

    public function test_citizen_cannot_submit_a_response(): void
    {
        $feedback = Feedback::factory()->create();

        $this->actingAs(User::factory()->create());

        try {
            Volt::test('feedback-admin')
                ->call('startResponding', $feedback->id)
                ->set('response', 'Mencoba menanggapi tanpa izin.')
                ->call('saveResponse');
        } catch (InvalidArgumentException|HttpException) {

        }

        $feedback->refresh();

        $this->assertNull($feedback->response);
        $this->assertNull($feedback->responded_at);
    }
}
