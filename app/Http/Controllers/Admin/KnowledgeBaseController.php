<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(): View
    {
        return view('admin.knowledge.index', [
            'items' => KnowledgeBaseItem::query()->latest()->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['required', 'string'],
            'ai_visible' => ['sometimes', 'boolean'],
        ]) + ['ai_visible' => $request->boolean('ai_visible')];

        KnowledgeBaseItem::query()->create($data);

        return back()->with('success', 'Knowledge item created.');
    }
}
