<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ReportDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_detail_is_public(): void
    {
        $report = Report::factory()->create(['title' => 'Jembatan penyeberangan retak']);

        $this->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('Jembatan penyeberangan retak')
            ->assertSee('Riwayat status')
            ->assertSee('Menunggu');
    }

    public function test_authenticated_user_can_comment_on_a_report(): void
    {
        $user = User::factory()->create(['name' => 'Warga Peduli']);
        $report = Report::factory()->create();

        $this->actingAs($user);

        Volt::test('report-detail', ['reportUuid' => $report->uuid])
            ->set('comment', 'Kondisinya semakin parah setelah hujan.')
            ->call('addComment')
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'success');

        $this->assertDatabaseHas('comments', [
            'report_id' => $report->id,
            'user_id' => $user->id,
            'body' => 'Kondisinya semakin parah setelah hujan.',
        ]);
    }

    public function test_guest_cannot_comment_on_a_report(): void
    {
        $report = Report::factory()->create();

        Volt::test('report-detail', ['reportUuid' => $report->uuid])
            ->set('comment', 'Mencoba menanggapi tanpa akun.')
            ->call('addComment')
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_admin_can_update_report_status_with_notes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = Report::factory()->create();

        $this->actingAs($admin);

        Volt::test('report-detail', ['reportUuid' => $report->uuid])
            ->set('status', Report::STATUS_RESOLVED)
            ->set('notes', 'Perbaikan selesai dikerjakan petugas.')
            ->call('updateStatus')
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'success');

        $this->assertSame(Report::STATUS_RESOLVED, $report->fresh()->status);
        $this->assertDatabaseHas('status_histories', [
            'report_id' => $report->id,
            'user_id' => $admin->id,
            'status' => Report::STATUS_RESOLVED,
            'notes' => 'Perbaikan selesai dikerjakan petugas.',
        ]);
    }

    public function test_non_admin_cannot_update_report_status(): void
    {
        $report = Report::factory()->create();
        $citizen = User::factory()->create();

        $this->actingAs($citizen);

        Volt::test('report-detail', ['reportUuid' => $report->uuid])
            ->set('status', Report::STATUS_RESOLVED)
            ->set('notes', 'Mencoba mengubah status tanpa izin.')
            ->call('updateStatus')
            ->assertHasErrors(['status'])
            ->assertDispatched('notify', type: 'error');

        $this->assertSame(Report::STATUS_PENDING, $report->fresh()->status);
        $this->assertDatabaseMissing('status_histories', [
            'report_id' => $report->id,
            'user_id' => $citizen->id,
        ]);
    }

    public function test_status_history_is_created_when_report_is_submitted(): void
    {
        $report = Report::factory()->create();

        $this->assertDatabaseHas('status_histories', [
            'report_id' => $report->id,
            'status' => Report::STATUS_PENDING,
        ]);
    }

    public function test_report_urls_use_the_public_uuid_instead_of_the_numeric_id(): void
    {
        $report = Report::factory()->create();

        $this->assertSame(url('/laporan/'.$report->uuid), route('laporan.show', $report));
        $this->assertNotSame(url('/laporan/'.$report->id), route('laporan.show', $report));

        $this->get('/laporan/'.$report->id)->assertNotFound();
    }

    public function test_reporter_identity_is_hidden_from_other_visitors(): void
    {
        $reporter = User::factory()->create(['name' => 'Pelapor Rahasia']);
        $report = Report::factory()->create(['user_id' => $reporter->id]);

        $this->get(route('laporan.show', $report))
            ->assertOk()
            ->assertDontSee('Pelapor Rahasia');
    }

    public function test_reporter_identity_is_visible_to_admins(): void
    {
        $reporter = User::factory()->create(['name' => 'Pelapor Rahasia']);
        $report = Report::factory()->create(['user_id' => $reporter->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('laporan.show', $report))
            ->assertOk()
            ->assertSee('Pelapor Rahasia');
    }

    public function test_evidence_photo_exposes_a_preview_trigger(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('reports/bukti-jalan.jpg', 'fake-image-bytes');

        $report = Report::factory()->create(['photo_path' => 'reports/bukti-jalan.jpg']);

        Volt::test('report-detail', ['reportUuid' => $report->uuid])
            ->assertSee('Pratinjau foto bukti')
            ->assertSee('Buka foto di tab baru')
            ->assertSee(route('laporan.foto', $report));
    }

    public function test_evidence_photo_is_streamed_by_the_application(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('reports/bukti-jalan.jpg', 'fake-image-bytes');

        $report = Report::factory()->create(['photo_path' => 'reports/bukti-jalan.jpg']);

        $response = $this->get(route('laporan.foto', $report));

        $response->assertOk();
        $this->assertSame('fake-image-bytes', $response->streamedContent());
    }

    public function test_missing_evidence_photo_is_hidden_and_returns_not_found(): void
    {
        Storage::fake('public');

        $report = Report::factory()->create(['photo_path' => 'reports/hilang.jpg']);

        $this->assertNull($report->photo_url);

        Volt::test('report-detail', ['reportUuid' => $report->uuid])
            ->assertDontSee('Pratinjau foto bukti');

        $this->get(route('laporan.foto', $report))->assertNotFound();
    }

    public function test_comment_submissions_are_rate_limited(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->create();

        $this->actingAs($user);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            Volt::test('report-detail', ['reportUuid' => $report->uuid])
                ->set('comment', 'Tanggapan nomor '.$attempt.'.')
                ->call('addComment')
                ->assertHasNoErrors();
        }

        Volt::test('report-detail', ['reportUuid' => $report->uuid])
            ->set('comment', 'Tanggapan keenam yang seharusnya ditolak.')
            ->call('addComment')
            ->assertHasErrors(['comment']);

        $this->assertDatabaseCount('comments', 5);
    }
}
