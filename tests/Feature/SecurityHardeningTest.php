<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_role_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'Percobaan Eskalasi',
            'email' => 'eskalasi@example.test',
            'password' => 'rahasia-sekali',
            'role' => 'admin',
        ]);

        $this->assertSame('warga', $user->fresh()->role);
        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_feedback_from_citizens_is_never_rendered_publicly(): void
    {
        Feedback::factory()->create([
            'message' => 'Pesan rahasia warga tentang tetangga.',
            'contact' => 'rahasia@example.test',
        ]);

        $this->get(route('feedback'))
            ->assertOk()
            ->assertDontSee('Pesan rahasia warga tentang tetangga.')
            ->assertDontSee('rahasia@example.test');
    }

    public function test_evidence_uploads_reject_unsupported_image_types(): void
    {
        $category = Category::factory()->create();

        Volt::test('report-form')
            ->set('title', 'Lampiran tidak didukung')
            ->set('description', 'Menguji penyaringan tipe berkas lampiran.')
            ->set('category_id', (string) $category->id)
            ->set('latitude', '3.59520000')
            ->set('longitude', '98.67220000')
            ->set('photo', UploadedFile::fake()->create('bukti.svg', 20, 'image/svg+xml'))
            ->call('submit')
            ->assertHasErrors(['photo']);

        $this->assertDatabaseCount('reports', 0);
    }
}
