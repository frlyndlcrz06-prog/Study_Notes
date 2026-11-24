<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_a_valid_note(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/notes', [
            'title' => 'Study plan',
            'body' => 'Read chapter 3 tonight.',
        ]);

        $response->assertCreated()->assertJsonFragment(['title' => 'Study plan']);
        $this->assertDatabaseCount('notes', 1);
        $this->assertDatabaseHas('notes', [
            'title' => 'Study plan',
            'body' => 'Read chapter 3 tonight.',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_id_sent_by_the_client_is_ignored(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->postJson('/notes', [
            'title' => 'Mine only',
            'user_id' => $other->id,
        ])->assertCreated();

        $this->assertDatabaseHas('notes', ['title' => 'Mine only', 'user_id' => $user->id]);
        $this->assertDatabaseMissing('notes', ['user_id' => $other->id]);
    }

    public function test_note_without_a_title_is_refused_and_nothing_is_saved(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/notes', ['body' => 'A body but no title']);

        $response->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->assertDatabaseCount('notes', 0);
    }

    public function test_note_with_a_too_short_title_is_refused_and_nothing_is_saved(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/notes', ['title' => 'ab']);

        $response->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->assertDatabaseCount('notes', 0);
    }

    public function test_guest_cannot_list_notes(): void
    {
        Note::factory()->create();

        $this->getJson('/notes')->assertUnauthorized();
    }

    public function test_guest_cannot_create_a_note(): void
    {
        $this->postJson('/notes', ['title' => 'Guest note'])->assertUnauthorized();

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_guest_cannot_delete_a_note(): void
    {
        $note = Note::factory()->create();

        $this->deleteJson("/notes/{$note->id}")->assertUnauthorized();

        $this->assertDatabaseHas('notes', ['id' => $note->id]);
    }

    public function test_user_sees_only_their_own_notes(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Note::factory()->for($user)->create(['title' => 'My first note']);
        Note::factory()->withoutBody()->for($user)->create(['title' => 'My empty note']);
        Note::factory()->for($other)->create(['title' => 'Someone else note']);

        $response = $this->actingAs($user)->getJson('/notes');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['title' => 'My first note'])
            ->assertJsonFragment(['title' => 'My empty note'])
            ->assertJsonMissing(['title' => 'Someone else note']);
        $this->assertDatabaseCount('notes', 3);
    }

    public function test_user_can_delete_their_own_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->for($user)->create();

        $this->actingAs($user)->deleteJson("/notes/{$note->id}")->assertNoContent();

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_user_cannot_delete_another_users_note(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $note = Note::factory()->for($owner)->create();

        $this->actingAs($intruder)->deleteJson("/notes/{$note->id}")->assertForbidden();

        $this->assertDatabaseHas('notes', ['id' => $note->id]);
    }
}