<?php

namespace Tests\Feature\Workspace;

use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\TaskTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WorkspaceScenario;
use Tests\TestCase;

/** Template, widget dashboard, Monitoring, detail intern, dan rekap sesuai kontrak frontend. */
class InsightContractTest extends TestCase
{
    use RefreshDatabase, WorkspaceScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpScenario();
    }

    private function templatePayload(array $extra = []): array
    {
        return $extra + [
            'title' => 'Laporan mingguan',
            'description' => 'Rangkum **pekerjaan**',
            'priority' => 'high',
            'due_in_days' => 5,
            'checklist' => [['content' => 'Ringkasan'], ['content' => 'Kendala']],
            'is_onboarding' => false,
        ];
    }

    // ------------------------------------------------------------------ template

    public function test_buat_template_dengan_payload_frontend(): void
    {
        $label = TaskLabel::create(['team_id' => $this->gta->id, 'name' => 'dokumen', 'color' => '#f59e0b'])->id;
        $foreign = TaskLabel::create(['team_id' => $this->dea->id, 'name' => 'dea', 'color' => '#111111'])->id;

        $response = $this->actingAs($this->rudy->user)
            ->postJson('/workspace/templates', $this->templatePayload(['label_ids' => [$label, $foreign]]))
            ->assertCreated();

        $response->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.due_in_days', 5)
            ->assertJsonPath('data.team.id', $this->gta->id)
            ->assertJsonPath('data.checklist', [['content' => 'Ringkasan'], ['content' => 'Kendala']])
            ->assertJsonPath('data.description_html', '<p>Rangkum <strong>pekerjaan</strong></p>')
            ->assertJsonPath('data.usage_count', 0)
            ->assertJsonPath('data.can.assign', true);
        $this->assertSame([$label], collect($response->json('data.labels'))->pluck('id')->all(), 'label tim lain dibuang');

        $this->assertSame(['Ringkasan', 'Kendala'], TaskTemplate::first()->checklist);
    }

    public function test_validasi_template(): void
    {
        $post = fn (array $extra) => $this->actingAs($this->rudy->user)->postJson('/workspace/templates', $this->templatePayload($extra));

        $post(['due_in_days' => 91])->assertUnprocessable();
        $post(['due_in_days' => -1])->assertUnprocessable();
        $post(['priority' => 'sangat'])->assertUnprocessable();
        $post(['title' => ''])->assertUnprocessable();
    }

    public function test_halaman_kelola_template_dan_hak_akses(): void
    {
        $this->actingAs($this->rudy->user)->postJson('/workspace/templates', $this->templatePayload())->assertCreated();

        $this->actingAs($this->rudy->user)->get('/workspace/templates')->assertOk()->assertSee('Laporan mingguan');
        $this->actingAs($this->admin)->get('/workspace/templates')->assertOk();
        $this->actingAs($this->ahmad->user)->get('/workspace/templates')->assertForbidden();

        $list = $this->actingAs($this->rudy->user)->getJson('/workspace/templates')->assertOk()->json('data');
        $this->assertSame('Laporan mingguan', $list[0]['title']);
        $this->assertArrayHasKey('usage_count', $list[0]);
    }

    public function test_tugaskan_template_ke_banyak_intern_membuat_tugas_di_space_masing_masing(): void
    {
        $label = TaskLabel::create(['team_id' => $this->gta->id, 'name' => 'dokumen', 'color' => '#f59e0b'])->id;
        $template = $this->actingAs($this->admin)->postJson('/workspace/templates', $this->templatePayload(['team_id' => $this->gta->id, 'label_ids' => [$label]]))->json('data.id');
        $due = $this->due(4);

        $response = $this->actingAs($this->admin)->postJson("/workspace/templates/{$template}/assign", [
            'assignee_ids' => [$this->ahmad->id, $this->siti->id],
            'due_at' => $due,
        ])->assertCreated();

        $response->assertJsonPath('meta.created', 2)->assertJsonPath('meta.template.usage_count', 2);
        $this->assertEqualsCanonicalizing(
            [$this->spAhmad->id, $this->spSiti->id],
            collect($response->json('data'))->pluck('workspace.id')->all()
        );
        $this->assertSame('high', $response->json('data.0.priority'));
        $this->assertSame($label, $response->json('data.0.labels.0.id'));
        $this->assertSame(2, $response->json('data.0.checklist.total'));

        $this->assertSame(1, $this->notificationCount($this->ahmad->user, 'App\Notifications\TaskAssignedNotification'));
        $this->assertSame(1, $this->notificationCount($this->siti->user, 'App\Notifications\TaskAssignedNotification'));
        $this->assertSame(2, Task::where('template_id', $template)->count());
    }

    public function test_tugaskan_template_ditolak_untuk_intern_tanpa_space_atau_di_luar_bimbingan(): void
    {
        $template = $this->actingAs($this->rudy->user)->postJson('/workspace/templates', $this->templatePayload())->json('data.id');
        $assign = fn ($user, array $ids) => $this->actingAs($user)->postJson("/workspace/templates/{$template}/assign", ['assignee_ids' => $ids, 'due_at' => $this->due()]);

        // Siti dibimbing Tasmil, bukan Rudy.
        $assign($this->rudy->user, [$this->ahmad->id, $this->siti->id])->assertForbidden();
        $this->assertSame(0, Task::count());

        $this->spAhmad->update(['is_archived' => true]);
        $assign($this->rudy->user, [$this->ahmad->id])->assertUnprocessable()->assertJsonValidationErrors('assignee_ids');

        $assign($this->ahmad->user, [$this->ahmad->id])->assertForbidden();
        $this->actingAs($this->admin)->postJson("/workspace/templates/{$template}/assign", ['assignee_ids' => [$this->siti->id]])->assertUnprocessable();
    }

    public function test_simpan_tugas_sebagai_template_membawa_prioritas_dan_label(): void
    {
        $label = TaskLabel::create(['team_id' => $this->gta->id, 'name' => 'api', 'color' => '#6366f1'])->id;
        $id = $this->makeTaskForAhmad(['title' => 'Integrasi API', 'priority' => 'urgent', 'label_ids' => [$label]]);

        $this->actingAs($this->rudy->user)->postJson("/workspace/tasks/{$id}/template")
            ->assertCreated()
            ->assertJsonPath('data.priority', 'urgent')
            ->assertJsonPath('data.labels.0.id', $label);
    }

    // ------------------------------------------------------------------ widget dashboard

    public function test_ringkasan_intern(): void
    {
        $a = $this->makeTaskForAhmad(['title' => 'Dekat', 'due_at' => $this->due(1)]);
        $b = $this->makeTaskForAhmad(['title' => 'Jauh', 'due_at' => $this->due(9)]);
        $this->setStatus($this->ahmad->user, $a, 'review')->assertOk();
        $this->actingAs($this->rudy->user)->patchJson("/workspace/tasks/{$a}/review", ['decision' => 'revision', 'feedback' => 'Ulangi'])->assertOk();
        Task::whereKey($b)->update(['overdue_at' => now()]);
        $this->sendMessage($this->rudy->user, $this->dmAhmad, 'Halo, sudah dicek?')->assertCreated();

        $response = $this->actingAs($this->ahmad->user)->getJson('/workspace/summary')->assertOk();

        $response->assertJsonPath('role', 'intern')
            ->assertJsonPath('pending_mentor', false)
            ->assertJsonPath('workspace_url', route('workspace.show', $this->spAhmad))
            ->assertJsonPath('my_tasks.counts', ['active' => 2, 'revision' => 1, 'late' => 1])
            ->assertJsonPath('my_tasks.data.0.title', 'Dekat')
            ->assertJsonPath('my_tasks.data.0.workspace.id', $this->spAhmad->id);

        $unread = collect($response->json('unread_channels'))->firstWhere('channel.id', $this->dmAhmad->id);
        $this->assertGreaterThanOrEqual(1, $unread['unread_count']);
        $this->assertSame('Halo, sudah dicek?', $unread['last_message']['body']);
        $this->assertSame('Rudy Hermayadi', $unread['last_message']['user']['name']);
        $this->assertSame(route('workspace.show', $this->spAhmad) . "?tab=channel&channel={$this->dmAhmad->id}", $unread['url']);
        $this->assertCount(2, $unread['channel']['members']);

        // dibisukan: hilang dari widget
        $this->actingAs($this->ahmad->user)->patchJson("/workspace/channels/{$this->dmAhmad->id}/mute", ['is_muted' => true])->assertOk();
        $after = $this->actingAs($this->ahmad->user)->getJson('/workspace/summary')->json('unread_channels');
        $this->assertNull(collect($after)->firstWhere('channel.id', $this->dmAhmad->id));
    }

    public function test_ringkasan_intern_tanpa_space(): void
    {
        $this->spAhmad->delete();

        $this->actingAs($this->ahmad->user)->getJson('/workspace/summary')
            ->assertOk()->assertJsonPath('pending_mentor', true)->assertJsonPath('workspace_url', route('workspace.index'));
    }

    public function test_ringkasan_mentor_review_terlama_dulu_dan_terlambat_per_intern(): void
    {
        $first = $this->makeTaskForAhmad(['title' => 'Duluan']);
        $this->setStatus($this->ahmad->user, $first, 'review')->assertOk();
        $this->travel(2)->hours();
        $second = $this->makeTaskForAhmad(['title' => 'Belakangan']);
        $this->setStatus($this->ahmad->user, $second, 'review')->assertOk();

        $late = $this->makeTaskForAhmad(['title' => 'Telat']);
        Task::whereKey($late)->update(['overdue_at' => now(), 'due_at' => now()->subDay()]);

        $response = $this->actingAs($this->rudy->user)->getJson('/workspace/summary')->assertOk();

        $response->assertJsonPath('role', 'mentor')
            ->assertJsonPath('to_review.total', 2)
            ->assertJsonPath('to_review.data.0.title', 'Duluan')
            ->assertJsonPath('to_review.data.1.title', 'Belakangan')
            ->assertJsonPath('ungrouped_interns', [])
            ->assertJsonPath('groups_url', route('workspace.index'));
        $this->assertNotEmpty($response->json('to_review.data.0.submitted_at'));

        $response->assertJsonCount(1, 'late_by_intern')
            ->assertJsonPath('late_by_intern.0.intern.id', $this->ahmad->id)
            ->assertJsonPath('late_by_intern.0.late_count', 1)
            ->assertJsonPath('late_by_intern.0.tasks.0.code', 'T-3')
            ->assertJsonPath('late_by_intern.0.workspace.id', $this->spAhmad->id);

        // mentor lain tidak melihat tugas Ahmad
        $this->actingAs($this->herman->user)->getJson('/workspace/summary')->assertJsonPath('to_review.total', 0);
        $this->actingAs($this->admin)->getJson('/workspace/summary')->assertJsonPath('to_review.total', 2);
    }

    // ------------------------------------------------------------------ Monitoring

    public function test_progress_monitoring_hanya_untuk_admin(): void
    {
        $a = $this->makeTaskForAhmad();
        $b = $this->makeTaskForAhmad();
        $this->makeTaskForAhmad();
        $this->setStatus($this->ahmad->user, $a, 'review')->assertOk();
        Task::whereKey($b)->update(['status' => 'done', 'completed_at' => now()]);
        Task::whereKey($this->makeTaskForAhmad())->update(['overdue_at' => now()]);

        $response = $this->actingAs($this->admin)->getJson("/workspace/progress?intern_ids[]={$this->ahmad->id}&intern_ids[]={$this->budi->id}")->assertOk();

        $response->assertJsonPath("interns.{$this->ahmad->id}.total", 4)
            ->assertJsonPath("interns.{$this->ahmad->id}.done", 1)
            ->assertJsonPath("interns.{$this->ahmad->id}.review", 1)
            ->assertJsonPath("interns.{$this->ahmad->id}.late", 1)
            ->assertJsonPath("interns.{$this->ahmad->id}.group_url", route('workspace.show', $this->spAhmad))
            ->assertJsonPath("interns.{$this->budi->id}.total", 0);

        $gta = collect($response->json('teams'))->firstWhere('team.id', $this->gta->id);
        $this->assertSame(['todo' => 2, 'in_progress' => 0, 'review' => 1, 'revision' => 0, 'done' => 1, 'late' => 1], $gta['stats']);
        $this->assertSame(route('workspace.index', ['team' => $this->gta->id]), $gta['workspace_url']);
        $this->assertGreaterThanOrEqual(2, $gta['groups_count']);
        $this->assertSame(['Tasmil'], collect($gta['idle_mentors'])->pluck('name')->all(), 'Rudy sudah memberi tugas minggu ini');

        $this->actingAs($this->rudy->user)->getJson('/workspace/progress')->assertForbidden();
        $this->actingAs($this->ahmad->user)->getJson('/workspace/progress')->assertForbidden();
    }

    public function test_progress_tanpa_intern_ids_dan_hasil_kosong_berupa_objek(): void
    {
        $raw = $this->actingAs($this->admin)->getJson('/workspace/progress')->assertOk()->getContent();

        $this->assertStringContainsString('"interns":{}', $raw);
    }

    // ------------------------------------------------------------------ detail intern

    public function test_tugas_per_intern_untuk_admin_dan_mentor_pembimbing(): void
    {
        $done = $this->makeTaskForAhmad(['title' => 'Selesai']);
        $open = $this->makeTaskForAhmad(['title' => 'Berjalan']);
        $this->setStatus($this->ahmad->user, $done, 'review')->assertOk();
        $this->actingAs($this->rudy->user)->patchJson("/workspace/tasks/{$done}/review", ['decision' => 'approve', 'score' => 80])->assertOk();

        $response = $this->actingAs($this->rudy->user)->getJson("/workspace/interns/{$this->ahmad->id}/tasks")->assertOk();

        $response->assertJsonPath('data.0.title', 'Berjalan')
            ->assertJsonPath('data.1.title', 'Selesai')
            ->assertJsonPath('data.1.score', 80)
            ->assertJsonPath('data.1.workspace.id', $this->spAhmad->id)
            ->assertJsonPath('stats.done', 1)
            ->assertJsonPath('stats.todo', 1)
            ->assertJsonPath('stats.avg_score', 80)
            ->assertJsonPath('group.id', $this->spAhmad->id)
            ->assertJsonPath('space_url', route('workspace.show', $this->spAhmad));

        $this->actingAs($this->admin)->getJson("/workspace/interns/{$this->ahmad->id}/tasks")->assertOk();
        $this->actingAs($this->tasmil->user)->getJson("/workspace/interns/{$this->ahmad->id}/tasks")->assertForbidden();
        $this->actingAs($this->ahmad->user)->getJson("/workspace/interns/{$this->ahmad->id}/tasks")->assertForbidden();
    }

    // ------------------------------------------------------------------ rekap

    public function test_rekap_tugas_selesai_intern(): void
    {
        $a = $this->makeTaskForAhmad(['title' => 'Tepat waktu', 'due_at' => $this->due(5)]);
        $b = $this->makeTaskForAhmad(['title' => 'Terlambat', 'due_at' => $this->due(5)]);
        $this->makeTaskForAhmad(['title' => 'Masih jalan']);
        foreach ([$a, $b] as $id) {
            $this->setStatus($this->ahmad->user, $id, 'review')->assertOk();
        }
        $this->actingAs($this->rudy->user)->patchJson("/workspace/tasks/{$a}/review", ['decision' => 'approve', 'score' => 90])->assertOk();
        $this->actingAs($this->rudy->user)->patchJson("/workspace/tasks/{$b}/review", ['decision' => 'approve', 'score' => 70])->assertOk();
        Task::whereKey($b)->update(['due_at' => now()->subDay()]);

        $json = $this->actingAs($this->ahmad->user)->getJson('/workspace/recap')->assertOk();

        $json->assertJsonPath('data.intern.id', $this->ahmad->id)
            ->assertJsonPath('data.summary', ['done' => 2, 'active' => 1, 'on_time' => 1, 'avg_score' => 80, 'min_score' => 70, 'max_score' => 90])
            ->assertJsonCount(2, 'data.tasks');
        $this->assertTrue(collect($json->json('data.tasks'))->firstWhere('title', 'Tepat waktu')['on_time']);
        $this->assertFalse(collect($json->json('data.tasks'))->firstWhere('title', 'Terlambat')['on_time']);

        $this->actingAs($this->ahmad->user)->get('/workspace/recap')->assertOk()->assertSee('Tepat waktu');
        $this->actingAs($this->rudy->user)->get("/workspace/recap?intern_id={$this->ahmad->id}")->assertOk()->assertSee('Ahmad Fauzi');
    }

    public function test_hak_akses_rekap(): void
    {
        $this->actingAs($this->rudy->user)->getJson('/workspace/recap')->assertUnprocessable();
        $this->actingAs($this->tasmil->user)->getJson("/workspace/recap?intern_id={$this->ahmad->id}")->assertForbidden();
        $this->actingAs($this->ahmad->user)->getJson("/workspace/recap?intern_id={$this->siti->id}")->assertForbidden();
        $this->actingAs($this->admin)->getJson("/workspace/recap?intern_id={$this->siti->id}")->assertOk()->assertJsonPath('data.summary.avg_score', null);
    }
}
