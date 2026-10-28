<?php

namespace Tests\Feature\Workspace;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WorkspaceScenario;
use Tests\TestCase;

/** Halaman HTML Workspace dirender dari data database. */
class PagesTest extends TestCase
{
    use RefreshDatabase, WorkspaceScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpScenario();
    }

    public function test_intern_diarahkan_ke_space_pribadinya(): void
    {
        $this->actingAs($this->ahmad->user)->get('/workspace')
            ->assertRedirect(route('workspace.show', $this->spAhmad));
    }

    public function test_mentor_dan_admin_melihat_daftar_kelompok(): void
    {
        $this->actingAs($this->rudy->user)->get('/workspace')
            ->assertOk()
            ->assertSee($this->wsGta->name)
            ->assertSee($this->spAhmad->name)
            ->assertDontSee($this->wsDea->name);

        $this->actingAs($this->admin)->get('/workspace')
            ->assertOk()
            ->assertSee($this->wsDea->name);
    }

    public function test_filter_pencarian_daftar_kelompok(): void
    {
        $this->actingAs($this->admin)->get('/workspace?q=' . urlencode('Ahmad'))
            ->assertOk()
            ->assertSee($this->spAhmad->name)
            ->assertDontSee($this->wsDea->name);
    }

    public function test_halaman_detail_kelompok_menampilkan_tugas_dan_channel(): void
    {
        $id = $this->makeTaskForAhmad(['title' => 'Integrasi API absensi']);

        $this->actingAs($this->rudy->user)->get("/workspace/{$this->spAhmad->id}")
            ->assertOk()
            ->assertSee('Integrasi API absensi')
            ->assertSee('T-1')
            ->assertSee($this->dmAhmad->name)
            ->assertSee($this->announcement->name);

        $this->actingAs($this->ahmad->user)->get("/workspace/{$this->spAhmad->id}?tab=list")->assertOk();
    }

    public function test_detail_kelompok_ditolak_untuk_non_anggota(): void
    {
        $this->actingAs($this->ahmad->user)->get("/workspace/{$this->spSiti->id}")->assertForbidden();
        $this->actingAs($this->herman->user)->get("/workspace/{$this->wsGta->id}")->assertForbidden();
    }

    public function test_detail_tugas_html_dan_json(): void
    {
        $id = $this->makeTaskForAhmad(['title' => 'Tugas penting', 'description' => 'Baca **dokumen**']);

        $this->actingAs($this->rudy->user)->get("/workspace/tasks/{$id}")
            ->assertOk()
            ->assertSee('Tugas penting');

        $json = $this->actingAs($this->rudy->user)->getJson("/workspace/tasks/{$id}")->assertOk();
        $json->assertJsonPath('data.code', 'T-1')
            ->assertJsonPath('data.status', 'todo')
            ->assertJsonPath('data.can.review', true)
            ->assertJsonPath('data.can.comment', true)
            ->assertJsonPath('data.description_html', '<p>Baca <strong>dokumen</strong></p>')
            ->assertJsonPath('data.workspace.id', $this->spAhmad->id)
            ->assertJsonPath('data.assignees.0.id', $this->ahmad->id);

        $this->actingAs($this->ahmad->user)->getJson("/workspace/tasks/{$id}")
            ->assertOk()
            ->assertJsonPath('data.can.review', false);

        $this->actingAs($this->budi->user)->getJson("/workspace/tasks/{$id}")->assertForbidden();
    }

    public function test_html_pada_deskripsi_di_escape(): void
    {
        $id = $this->makeTaskForAhmad(['description' => '<script>alert(1)</script>']);

        $html = $this->actingAs($this->rudy->user)->getJson("/workspace/tasks/{$id}")->json('data.description_html');

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_halaman_lama_tetap_dirender_dengan_menu_workspace(): void
    {
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($this->admin)->get('/admin/monitoring')->assertOk();
        $this->actingAs($this->rudy->user)->get('/mentor/dashboard')->assertOk();
        $this->actingAs($this->rudy->user)->get('/mentor/interns')->assertOk();
        $this->actingAs($this->ahmad->user)->get('/intern/dashboard')->assertOk();
        $this->actingAs($this->ahmad->user)->get('/intern/logbook/create')->assertOk();
    }

    public function test_workspace_tim_membawa_semua_mentor_dan_jumlah_intern_sebenarnya(): void
    {
        $data = app(\App\Services\WorkspacePresenter::class)->workspace($this->wsGta, $this->rudy->user);

        $this->assertSame(4, $data['members_count']);
        $this->assertSame(2, $data['interns_count'], 'bukan members_count - 1');
        $this->assertEqualsCanonicalizing(['Rudy Hermayadi', 'Tasmil'], array_column($data['mentors'], 'name'));

        // Pratinjau avatar memuat satu mentor lalu intern, supaya kartu tetap punya avatar intern.
        $roles = array_column($data['members_preview'], 'role');
        $this->assertSame('mentor', $roles[0]);
        $this->assertSame(2, count(array_keys($roles, 'intern')));

        $space = app(\App\Services\WorkspacePresenter::class)->workspace($this->spAhmad, $this->rudy->user);
        $this->assertSame(1, $space['interns_count']);
        $this->assertCount(1, $space['mentors']);
    }

    public function test_halaman_menampilkan_banyak_mentor_dan_jumlah_intern(): void
    {
        $this->actingAs($this->rudy->user)->get("/workspace/{$this->wsGta->id}")
            ->assertOk()
            ->assertSee('Mentor: ', false)
            ->assertSee('Rudy Hermayadi, Tasmil');

        $this->actingAs($this->admin)->get('/workspace')->assertOk()->assertSee('2 anak magang');
    }
}
