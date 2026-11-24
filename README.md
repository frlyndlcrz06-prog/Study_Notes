# Study Notes Test Suite (Week 15)

A small Laravel + PostgreSQL project whose work is its tests. The Study Notes module lets a signed-in person list their own notes, add a note, and delete their own note. The tests protect that behavior and run on a separate PostgreSQL database.

## Run the tests

```bash
composer install
cp .env.example .env        # set the PostgreSQL DB_* values (development database: study_notes)
php artisan key:generate
php artisan migrate
php artisan test
```

The tests use a separate PostgreSQL database named `study_notes_test`, set in `phpunit.xml`. Create it once before running the tests:

```bash
createdb -U postgres study_notes_test
```

No password or key is stored in any committed file. Credentials live only in the local `.env`, which is not in the repository.

## What was tested

There are 14 tests: 4 unit tests of `excerpt()` (no request, no database) and 10 feature tests (with `RefreshDatabase`, a factory, and database assertions). Every test creates its own data and can run alone.

| Case | Test name | What it protects |
|---|---|---|
| Short body | `test_excerpt_returns_the_whole_body_when_it_is_shorter_than_the_limit` | A short body is returned unchanged, with nothing cut |
| Boundary: exactly at the limit | `test_excerpt_keeps_a_body_that_is_exactly_at_the_limit` | A body of exactly 40 characters gets no `...` |
| Boundary: one character past the limit | `test_excerpt_cuts_a_body_that_is_one_character_past_the_limit` | A body of 41 characters is cut to 40 and `...` is added |
| Missing body | `test_excerpt_of_a_missing_body_is_an_empty_string` | A note with no body gives an empty excerpt and does not crash |
| Happy path | `test_user_can_save_a_valid_note` | A valid note returns 201 and is saved with the signed-in user's id |
| Owner cannot be chosen | `test_user_id_sent_by_the_client_is_ignored` | A client cannot create a note for another person by sending `user_id` |
| Bad input: no title | `test_note_without_a_title_is_refused_and_nothing_is_saved` | A missing title returns 422 and no row is saved |
| Bad input: title too short | `test_note_with_a_too_short_title_is_refused_and_nothing_is_saved` | A 2-character title returns 422 and no row is saved |
| Guest lists notes | `test_guest_cannot_list_notes` | A guest gets 401 and cannot read notes |
| Guest creates a note | `test_guest_cannot_create_a_note` | A guest gets 401 and nothing is saved |
| Guest deletes a note | `test_guest_cannot_delete_a_note` | A guest gets 401 and the note still exists |
| Wrong user, list | `test_user_sees_only_their_own_notes` | A list shows only the signed-in user's notes, including one without a body |
| Delete own note | `test_user_can_delete_their_own_note` | The owner gets 204 and the row is removed |
| Wrong user, delete | `test_user_cannot_delete_another_users_note` | Another person gets 403 and the note still exists |

The factory is `NoteFactory`, with the named state `withoutBody()`.

## Red then green

For the proof, the check `abort_unless($note->user_id === $request->user()->id, 403);` in `NoteController::destroy` was commented out on purpose. The test `test_user_cannot_delete_another_users_note` then failed because the response was 204 instead of 403. After the line was restored, the same test passed. The failing run is kept as a screenshot in the PDF.

## Questions

**Why do our tests use a separate database, and what could happen if they did not?**
The feature tests use `RefreshDatabase`, which rebuilds the tables and fills them with test rows. If the tests ran on the development database, they could erase or overwrite real notes and users, and fake rows could end up mixed with real data. The results would also depend on whatever data was already there, so the tests could pass or fail for the wrong reason. A separate `study_notes_test` database is safe to wipe and always starts clean.

**What is one case our suite does not cover yet, and what test would we add?**
We do not test the upper limits of the validation rules: a title of 100 versus 101 characters, or a body over 1000 characters. We only test a missing title and a title that is too short. We would add a test that posts a 100-character title and expects 201 with the row saved, and another that posts a 101-character title and expects 422 with nothing saved.

**Why does our validation test check that nothing was saved?**
A 422 response alone does not prove the bad data was rejected. The code could answer 422 and still save the row, or save it before it validates. Asserting that the `notes` table has no rows proves that the refusal had no side effects and that bad input never reaches the database.
