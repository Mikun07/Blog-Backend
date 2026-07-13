<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Tags retrieved.',
            'data' => Tag::withCount('blogs')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:tags,name'],
        ]);

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tag created.',
            'data' => $tag,
        ], 201);
    }
}
