<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Note::where('user_id', $request->user()->id)->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|min:3|max:100',
            'body' => 'nullable|string|max:1000',
        ]);

        $note = new Note($validated);
        $note->user_id = $request->user()->id;
        $note->save();

        return response()->json($note, 201);
    }

    public function destroy(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $note->delete();

        return response()->noContent();
    }
}
