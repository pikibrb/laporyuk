<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('kategori.index'))->assertRedirect(route('login'));
    }

    public function test_citizen_without_admin_role_cannot_access_category_management(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('kategori.index'))->assertForbidden();
    }

    public function test_admin_category_page_lists_categories(): void
    {
        Category::factory()->create(['name' => 'Fasilitas Olahraga']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('kategori.index'))
            ->assertOk()
            ->assertSee('Kelola Kategori')
            ->assertSee('Fasilitas Olahraga');
    }

    public function test_admin_can_create_a_category_with_generated_slug(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Volt::test('kategori-admin')
            ->set('name', 'Sungai Tercemar')
            ->call('createCategory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Sungai Tercemar',
            'slug' => 'sungai-tercemar',
        ]);
    }

    public function test_category_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Taman Kota']);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Volt::test('kategori-admin')
            ->set('name', 'Taman Kota')
            ->call('createCategory')
            ->assertHasErrors(['name'])
            ->assertSee('has already been taken', false);

        $this->assertSame(1, Category::query()->where('name', 'Taman Kota')->count());
    }

    public function test_admin_can_rename_a_category(): void
    {
        $category = Category::factory()->create(['name' => 'Nama Lama']);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Volt::test('kategori-admin')
            ->call('startEditing', $category->id)
            ->set('editName', 'Nama Baru')
            ->call('updateCategory')
            ->assertHasNoErrors();

        $category->refresh();

        $this->assertSame('Nama Baru', $category->name);
        $this->assertSame('nama-baru', $category->slug);
    }

    public function test_category_used_by_reports_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Report::factory()->create(['category_id' => $category->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Volt::test('kategori-admin')->call('deleteCategory', $category->id);

        $this->assertNotNull($category->fresh());
    }

    public function test_admin_can_delete_an_unused_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Volt::test('kategori-admin')->call('deleteCategory', $category->id);

        $this->assertNull($category->fresh());
    }

    public function test_confirming_a_delete_opens_the_confirmation_modal(): void
    {
        $category = Category::factory()->create(['name' => 'Sungai Tercemar']);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Volt::test('kategori-admin')
            ->call('confirmDelete', $category->id)
            ->assertSet('confirmingDeleteId', $category->id)
            ->assertSet('confirmingDeleteName', 'Sungai Tercemar')
            ->assertDispatched('open-modal', 'delete-category');
    }

    public function test_deleting_a_category_dispatches_a_success_notification(): void
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Volt::test('kategori-admin')
            ->call('deleteCategory', $category->id)
            ->assertSet('confirmingDeleteId', null)
            ->assertDispatched('notify', type: 'success');
    }

    public function test_deleting_a_used_category_dispatches_an_error_notification(): void
    {
        $category = Category::factory()->create();
        Report::factory()->create(['category_id' => $category->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Volt::test('kategori-admin')
            ->call('deleteCategory', $category->id)
            ->assertDispatched('notify', type: 'error');

        $this->assertNotNull($category->fresh());
    }

    public function test_new_category_appears_on_the_report_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Volt::test('kategori-admin')
            ->set('name', 'Pencemaran Sungai')
            ->call('createCategory')
            ->assertHasNoErrors();

        Volt::test('report-form')->assertSee('Pencemaran Sungai');
    }

    public function test_admin_sees_the_category_link_in_portal_navigation(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelola Kategori');

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Kelola Kategori');
    }
}
