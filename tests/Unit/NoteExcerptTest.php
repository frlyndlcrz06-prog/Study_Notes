<?php

namespace Tests\Unit;

use App\Models\Note;
use PHPUnit\Framework\TestCase;

class NoteExcerptTest extends TestCase
{
    public function test_excerpt_returns_the_whole_body_when_it_is_shorter_than_the_limit(): void
    {
        $note = new Note(['body' => 'Short note']);

        $this->assertSame('Short note', $note->excerpt());
    }

    public function test_excerpt_keeps_a_body_that_is_exactly_at_the_limit(): void
    {
        $body = str_repeat('a', 40);
        $note = new Note(['body' => $body]);

        $this->assertSame($body, $note->excerpt(40));
    }

    public function test_excerpt_cuts_a_body_that_is_one_character_past_the_limit(): void
    {
        $note = new Note(['body' => str_repeat('a', 41)]);

        $this->assertSame(str_repeat('a', 40).'...', $note->excerpt(40));
    }

    public function test_excerpt_of_a_missing_body_is_an_empty_string(): void
    {
        $note = new Note(['body' => null]);

        $this->assertSame('', $note->excerpt());
    }
}