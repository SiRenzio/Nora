<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SourceImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_import_an_asura_title_and_latest_chapter(): void
    {
        Http::fake(['asurascans.com/*' => Http::response($this->page('Regressed Mercenary', '104', '/comics/regressed-mercenary'), 200)]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('library.import'), $this->payload(
            'https://asurascans.com/comics/regressed-mercenary',
        ))->assertRedirect(route('library.index'));

        $this->assertDatabaseHas('titles', [
            'created_by_user_id' => $user->id,
            'title' => 'Regressed Mercenary',
            'content_type' => 'manhwa',
        ]);
        $this->assertDatabaseHas('library_entries', [
            'user_id' => $user->id,
            'source_website' => 'Asura Scans',
            'latest_chapter' => 'Chapter 104',
            'monitoring_enabled' => true,
        ]);
    }

    public function test_user_can_import_a_genz_toons_title(): void
    {
        Http::fake(['genztoons.org/*' => Http::response($this->page('Ability Devourer', '20', '/series/ability-devourer'), 200)]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('library.import'), $this->payload(
            'https://genztoons.org/series/ability-devourer',
        ))->assertRedirect(route('library.index'));

        $this->assertDatabaseHas('library_entries', [
            'user_id' => $user->id,
            'source_website' => 'Genz Toons',
            'latest_chapter' => 'Chapter 20',
        ]);
    }

    public function test_comix_and_unapproved_hosts_return_clear_validation_errors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('library.index'))->post(route('library.import'), $this->payload(
            'https://comix.to/title/example',
        ))->assertSessionHasErrors('source_url');

        $this->actingAs($user)->from(route('library.index'))->post(route('library.import'), $this->payload(
            'https://example.com/story',
        ))->assertSessionHasErrors('source_url');

        Http::assertNothingSent();
    }

    private function payload(string $url): array
    {
        return [
            'source_url' => $url,
            'status' => 'reading',
            'last_completed_chapter' => 'Chapter 5',
            'monitoring_enabled' => true,
        ];
    }

    private function page(string $title, string $latest, string $seriesPath): string
    {
        return <<<HTML
        <html><head>
        <meta property="og:title" content="{$title}">
        <meta property="og:description" content="A safe imported description.">
        <meta property="og:image" content="/covers/story.jpg">
        </head><body>
        <a href="/chapter-2">Chapter 2</a>
        <a href="{$seriesPath}/chapter/{$latest}"><span>Chapter {$latest}</span><span>6 days ago</span></a>
        <a href="/chapter/opaque-id"><span>Chapter {$latest}</span><span>3 days ago</span></a>
        <a href="/comics/recommended/chapter/999">Chapter 999</a>
        </body></html>
        HTML;
    }
}
