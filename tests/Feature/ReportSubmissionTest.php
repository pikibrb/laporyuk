<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_an_anonymous_report(): void
    {
        $category = Category::factory()->create();

        Volt::test('report-form')
            ->set('title', 'Jalan berlubang di depan pasar')
            ->set('description', 'Lubang cukup dalam dan membahayakan pengendara motor.')
            ->set('category_id', (string) $category->id)
            ->set('latitude', '3.59520000')
            ->set('longitude', '98.67220000')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submittedReportUuid', fn ($value) => is_string($value) && Str::isUuid($value));

        $report = Report::query()->firstOrFail();

        $this->assertSame('Jalan berlubang di depan pasar', $report->title);
        $this->assertNull($report->user_id);
        $this->assertSame(Report::STATUS_PENDING, $report->status);
        $this->assertDatabaseHas('status_histories', [
            'report_id' => $report->id,
            'status' => Report::STATUS_PENDING,
            'user_id' => null,
        ]);
    }

    public function test_authenticated_report_is_linked_to_the_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user);

        Volt::test('report-form')
            ->set('title', 'Pipa air bocor')
            ->set('description', 'Air menggenangi trotoar sejak pagi.')
            ->set('category_id', (string) $category->id)
            ->set('latitude', '3.59000000')
            ->set('longitude', '98.67000000')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reports', [
            'title' => 'Pipa air bocor',
            'user_id' => $user->id,
        ]);
    }

    public function test_report_requires_a_location_and_core_fields(): void
    {
        Volt::test('report-form')
            ->set('title', '')
            ->call('submit')
            ->assertHasErrors(['title', 'description', 'category_id', 'latitude', 'longitude']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_photo_must_be_an_image(): void
    {
        $category = Category::factory()->create();

        Volt::test('report-form')
            ->set('title', 'Laporan dengan lampiran salah')
            ->set('description', 'Lampiran bukan gambar.')
            ->set('category_id', (string) $category->id)
            ->set('latitude', '3.59000000')
            ->set('longitude', '98.67000000')
            ->set('photo', UploadedFile::fake()->create('bukti.pdf', 100))
            ->call('submit')
            ->assertHasErrors(['photo']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_report_stores_a_contact_phone_number(): void
    {
        $category = Category::factory()->create();

        Volt::test('report-form')
            ->set('title', 'Sampah menumpuk di pinggir jalan')
            ->set('description', 'Tumpukan sampah sudah dua minggu tidak diangkut.')
            ->set('phone', '0812 3456 7890')
            ->set('category_id', (string) $category->id)
            ->set('latitude', '3.59520000')
            ->set('longitude', '98.67220000')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reports', [
            'title' => 'Sampah menumpuk di pinggir jalan',
            'phone' => '0812 3456 7890',
        ]);
    }

    public function test_contact_phone_is_optional(): void
    {
        $category = Category::factory()->create();

        Volt::test('report-form')
            ->set('title', 'Lampu jalan mati')
            ->set('description', 'Lampu penerangan mati sejak semalam.')
            ->set('phone', '')
            ->set('category_id', (string) $category->id)
            ->set('latitude', '3.59520000')
            ->set('longitude', '98.67220000')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reports', [
            'title' => 'Lampu jalan mati',
            'phone' => null,
        ]);
    }

    public function test_contact_phone_rejects_invalid_characters(): void
    {
        $category = Category::factory()->create();

        Volt::test('report-form')
            ->set('title', 'Jalan berlubang')
            ->set('description', 'Lubang besar di tengah jalan.')
            ->set('phone', 'bukan nomor telepon')
            ->set('category_id', (string) $category->id)
            ->set('latitude', '3.59520000')
            ->set('longitude', '98.67220000')
            ->call('submit')
            ->assertHasErrors(['phone']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_selected_evidence_photo_is_previewed(): void
    {
        Volt::test('report-form')
            ->set('photo', UploadedFile::fake()->create('bukti-jalan.jpg', 10, 'image/jpeg'))
            ->assertSee('Pratinjau foto')
            ->assertSee('bukti-jalan.jpg');
    }

    public function test_previewed_evidence_photo_can_be_removed(): void
    {
        Volt::test('report-form')
            ->set('photo', UploadedFile::fake()->create('bukti-jalan.jpg', 10, 'image/jpeg'))
            ->call('removePhoto')
            ->assertSet('photo', null);
    }

    public function test_successful_submission_links_to_the_uuid_based_detail_page(): void
    {
        $category = Category::factory()->create();

        $component = Volt::test('report-form')
            ->set('title', 'Lampu taman kota padam')
            ->set('description', 'Lampu taman padam sejak dua malam yang lalu.')
            ->set('category_id', (string) $category->id)
            ->set('latitude', '3.59520000')
            ->set('longitude', '98.67220000')
            ->call('submit')
            ->assertHasNoErrors();

        $report = Report::query()->firstOrFail();

        $component
            ->assertSee(route('laporan.show', $report))
            ->assertSee(strtoupper(substr($report->uuid, 0, 8)))
            ->assertDontSee('/laporan/'.$report->id);
    }

    public function test_successful_submission_dispatches_a_notification(): void
    {
        $category = Category::factory()->create();

        Volt::test('report-form')
            ->set('title', 'Jembatan kayu rusak')
            ->set('description', 'Papan jembatan lapuk dan membahayakan pejalan kaki.')
            ->set('category_id', (string) $category->id)
            ->set('latitude', '3.59520000')
            ->set('longitude', '98.67220000')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'success');
    }
}
