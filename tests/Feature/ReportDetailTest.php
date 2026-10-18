<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        Volt::test('report-detail', ['reportId' => $report->id])
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

        Volt::test('report-detail', ['reportId' => $report->id])
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

        Volt::test('report-detail', ['reportId' => $report->id])
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

        Volt::test('report-detail', ['reportId' => $report->id])
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
}
