<?php

namespace Tests\Feature;

use App\Models\Hotline;
use App\Models\User;
use Database\Seeders\HotlineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class HotlineManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('hotline.index'))->assertRedirect(route('login'));
    }

    public function test_citizen_cannot_manage_emergency_numbers(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('hotline.index'))->assertForbidden();
    }

    public function test_admin_can_create_an_emergency_number(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Volt::test('hotline-admin')
            ->set('number', '112')
            ->set('label', 'Darurat nasional')
            ->set('description', 'Berlaku 24 jam.')
            ->set('sortOrder', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'success')
            ->assertSet('number', '');

        $this->assertDatabaseHas('hotlines', [
            'number' => '112',
            'label' => 'Darurat nasional',
            'is_active' => true,
        ]);
    }

    public function test_number_and_label_are_required_and_numbers_are_unique(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Hotline::factory()->create(['number' => '110']);

        Volt::test('hotline-admin')
            ->set('number', '110')
            ->set('label', '')
            ->call('save')
            ->assertHasErrors(['number', 'label']);

        $this->assertDatabaseCount('hotlines', 1);
    }

    public function test_admin_can_update_an_emergency_number(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $hotline = Hotline::factory()->create(['number' => '113', 'label' => 'Pemadam kebakaran']);

        Volt::test('hotline-admin')
            ->call('startEditing', $hotline->id)
            ->assertSet('number', '113')
            ->set('label', 'Dinas Pemadam Kebakaran')
            ->set('description', 'Panggilan darurat kebakaran.')
            ->call('save')
            ->assertHasNoErrors();

        $hotline->refresh();

        $this->assertSame('Dinas Pemadam Kebakaran', $hotline->label);
        $this->assertSame('Panggilan darurat kebakaran.', $hotline->description);
    }

    public function test_admin_can_deactivate_and_delete_a_number(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $hotline = Hotline::factory()->create(['number' => '115']);

        Volt::test('hotline-admin')
            ->call('toggleActive', $hotline->id)
            ->assertDispatched('notify', type: 'success');

        $this->assertFalse($hotline->fresh()->is_active);

        Volt::test('hotline-admin')
            ->call('deleteHotline', $hotline->id);

        $this->assertDatabaseMissing('hotlines', ['id' => $hotline->id]);
    }

    public function test_public_page_reads_numbers_from_the_database(): void
    {
        Hotline::factory()->create([
            'number' => '911',
            'label' => 'Layanan kota',
            'description' => 'Nomor baru dari basis data.',
        ]);

        $this->get(route('darurat'))
            ->assertOk()
            ->assertSee('Layanan kota')
            ->assertSee('Nomor baru dari basis data.')
            ->assertSee('href="tel:911"', false);
    }

    public function test_inactive_numbers_are_hidden_from_the_public_page(): void
    {
        Hotline::factory()->inactive()->create(['label' => 'Nomor tidak aktif']);

        $this->get(route('darurat'))
            ->assertOk()
            ->assertDontSee('Nomor tidak aktif')
            ->assertSee('Belum ada nomor penting yang terdaftar.');
    }

    public function test_default_numbers_are_available_through_the_seeder(): void
    {
        $this->seed(HotlineSeeder::class);

        $this->assertDatabaseCount('hotlines', 6);

        $this->get(route('darurat'))
            ->assertOk()
            ->assertSee('href="tel:112"', false)
            ->assertSee('href="tel:113"', false)
            ->assertSee('href="tel:123"', false);
    }

    public function test_dashboard_links_admins_to_emergency_number_management(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelola nomor penting')
            ->assertSee(route('hotline.index'));
    }
}
