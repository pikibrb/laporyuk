<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ResidentFeedbackInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('masukan.saya'))->assertRedirect(route('login'));
    }

    public function test_resident_sees_their_feedback_with_the_officer_response(): void
    {
        $resident = User::factory()->create();
        $admin = User::factory()->create(['name' => 'Petugas Rina', 'role' => 'admin']);

        Feedback::factory()->create([
            'user_id' => $resident->id,
            'message' => 'Mohon tambahkan mode gelap pada peta.',
            'response' => 'Mode gelap sudah aktif di seluruh halaman portal.',
            'responded_by' => $admin->id,
            'responded_at' => now(),
        ]);

        $this->actingAs($resident)
            ->get(route('masukan.saya'))
            ->assertOk()
            ->assertSee('Masukan Saya')
            ->assertSee('Mohon tambahkan mode gelap pada peta.')
            ->assertSee('Mode gelap sudah aktif di seluruh halaman portal.')
            ->assertSee('Petugas Rina')
            ->assertSee('Sudah ditanggapi');
    }

    public function test_resident_inbox_never_shows_other_peoples_feedback(): void
    {
        $resident = User::factory()->create();

        Feedback::factory()->create(['message' => 'Saran dari warga lain yang bersifat rahasia.']);
        Feedback::factory()->create(['user_id' => $resident->id, 'message' => 'Saran milik warga ini.']);

        $this->actingAs($resident)
            ->get(route('masukan.saya'))
            ->assertOk()
            ->assertSee('Saran milik warga ini.')
            ->assertDontSee('Saran dari warga lain yang bersifat rahasia.');
    }

    public function test_pending_feedback_is_marked_as_awaiting_a_response(): void
    {
        $resident = User::factory()->create();

        Feedback::factory()->create(['user_id' => $resident->id]);

        $this->actingAs($resident);

        Volt::test('feedback-history')
            ->assertSee('Menunggu tanggapan')
            ->assertSee('Masukan Anda sedang menunggu tanggapan petugas.')
            ->assertSeeHtml('>1</p>')
            ->assertSeeHtml('>0</p>');
    }

    public function test_empty_inbox_invites_the_resident_to_send_feedback(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('masukan.saya'))
            ->assertOk()
            ->assertSee('Anda belum pernah mengirim masukan dari akun ini.')
            ->assertSee(route('feedback'));
    }
}
