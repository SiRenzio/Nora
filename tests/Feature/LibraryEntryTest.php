<?php

namespace Tests\Feature;

use App\Models\LibraryEntry;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_the_library(): void
    {
        $this->get(route('library.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_add_a_manual_title_to_their_library(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('library.store'), $this->validEntry())
            ->assertRedirect(route('library.index'));

        $this->assertDatabaseHas('titles', [
            'created_by_user_id' => $user->id,
            'title' => 'The Beginning After the End',
            'content_type' => 'manhwa',
        ]);
        $this->assertDatabaseHas('library_entries', [
            'user_id' => $user->id,
            'status' => 'reading',
            'last_completed_chapter' => 'Chapter 12.5',
        ]);
    }

    public function test_user_only_sees_their_own_library_entries(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createEntry($user, 'My Story');
        $this->createEntry($otherUser, 'Private Story');

        $this->actingAs($user)->get(route('library.index'))
            ->assertOk()->assertSee('My Story')->assertDontSee('Private Story');
    }

    public function test_user_cannot_change_another_users_entry(): void
    {
        $user = User::factory()->create();
        $entry = $this->createEntry(User::factory()->create(), 'Private Story');

        $this->actingAs($user)->put(route('library.update', $entry), $this->validEntry())
            ->assertForbidden();
        $this->actingAs($user)->delete(route('library.destroy', $entry))
            ->assertForbidden();
    }

    public function test_user_can_update_and_archive_their_entry(): void
    {
        $user = User::factory()->create();
        $entry = $this->createEntry($user, 'Old Title');

        $this->actingAs($user)->put(route('library.update', $entry), [
            ...$this->validEntry(),
            'title' => 'Updated Title',
            'status' => 'completed',
        ])->assertRedirect(route('library.index'));

        $this->assertDatabaseHas('titles', ['id' => $entry->title_id, 'title' => 'Updated Title']);
        $this->assertDatabaseHas('library_entries', ['id' => $entry->id, 'status' => 'completed']);

        $this->actingAs($user)->delete(route('library.destroy', $entry))
            ->assertRedirect(route('library.index'));
        $this->assertNotNull($entry->fresh()->archived_at);
    }

    private function createEntry(User $user, string $name): LibraryEntry
    {
        $title = Title::create([
            'created_by_user_id' => $user->id,
            'title' => $name,
            'content_type' => 'novel',
        ]);

        return LibraryEntry::create([
            'user_id' => $user->id,
            'title_id' => $title->id,
            'status' => 'reading',
            'monitoring_enabled' => false,
        ]);
    }

    /** @return array<string, mixed> */
    private function validEntry(): array
    {
        return [
            'title' => 'The Beginning After the End',
            'alternative_title' => null,
            'content_type' => 'manhwa',
            'cover_url' => null,
            'description' => 'A fantasy adventure.',
            'source_url' => 'https://example.com/story',
            'source_website' => 'Example',
            'status' => 'reading',
            'latest_chapter' => 'Chapter 15',
            'last_completed_chapter' => 'Chapter 12.5',
            'last_read_at' => '2026-08-31 10:00:00',
            'monitoring_enabled' => false,
            'notes' => 'Continue this weekend.',
            'rating' => 8,
        ];
    }
}
