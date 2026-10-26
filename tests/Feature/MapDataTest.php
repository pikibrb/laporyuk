<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MapDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_endpoint_returns_marker_payload_for_guests(): void
    {
        $report = Report::factory()->create(['title' => 'Lampu taman padam']);

        $this->getJson(route('laporan.peta'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('markers.0.title', 'Lampu taman padam')
            ->assertJsonPath('markers.0.status', Report::STATUS_PENDING)
            ->assertJsonPath('markers.0.url', route('laporan.show', $report))
            ->assertJsonStructure(['markers' => [['title', 'category', 'status', 'status_label', 'latitude', 'longitude', 'color', 'url', 'created_ago']]]);
    }

    public function test_map_endpoint_respects_status_and_category_filters(): void
    {
        $category = Category::factory()->create(['name' => 'Penerangan Jalan Umum']);

        Report::factory()->resolved()->create(['category_id' => $category->id, 'title' => 'Sudah ditangani']);
        Report::factory()->create(['title' => 'Masih menunggu']);

        $this->getJson(route('laporan.peta', ['status' => Report::STATUS_RESOLVED]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('markers.0.title', 'Sudah ditangani');

        $this->getJson(route('laporan.peta', ['category' => $category->id]))
            ->assertOk()
            ->assertJsonPath('total', 1);

        $this->getJson(route('laporan.peta', ['search' => 'menunggu']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('markers.0.title', 'Masih menunggu');
    }

    public function test_map_endpoint_rejects_unknown_filter_values(): void
    {
        $this->getJson(route('laporan.peta', ['status' => 'tidak-ada']))->assertStatus(422);
        $this->getJson(route('laporan.peta', ['category' => 'abc']))->assertStatus(422);
    }

    public function test_map_endpoint_revalidates_with_etags(): void
    {
        Report::factory()->create();

        $response = $this->getJson(route('laporan.peta'))->assertOk();

        $etag = $response->headers->get('ETag');

        $this->assertNotNull($etag);

        $this->withHeaders(['If-None-Match' => $etag])
            ->getJson(route('laporan.peta'))
            ->assertStatus(304);
    }

    public function test_map_component_no_longer_polls_with_livewire(): void
    {
        Volt::test('report-map')
            ->assertDontSee('wire:poll', false)
            ->assertSee('data-map-url="'.route('laporan.peta').'"', false);
    }
}
