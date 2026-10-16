<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Firm\FirmContext;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CrmDocument;
use App\Models\Lawyer;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LegalCaseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cases.view', only: ['index', 'show']),
            new Middleware('permission:cases.manage', only: ['create', 'store', 'edit', 'update', 'updateNotes', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LegalCase::class);

        $cases = LegalCase::query()
            ->with(['client', 'lawyer.user', 'documents'])
            ->when($request->q, function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('case_number', 'like', $term)
                        ->orWhere('status', 'like', $term);
                });
            })
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('crm.cases.index', [
            'cases' => $cases,
            'filters' => $request->only('q', 'status'),
            'formClients' => Client::query()->orderBy('name')->get(['id', 'name', 'client_number']),
            'formLawyers' => Lawyer::query()->active()->with('user')->get(),
        ]);
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', LegalCase::class);

        return redirect()->route('cases.index', ['open' => 'create-case']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LegalCase::class);

        $data = $this->validated($request);
        $restrictedLawyerId = app(FirmContext::class)->restrictedLawyerId();
        if ($restrictedLawyerId !== null) {
            $data['lawyer_id'] = $restrictedLawyerId;
        }

        $images = $this->uploadedList($request, 'images');
        $files = $this->uploadedList($request, 'files');

        if (count($images) > 2) {
            return back()->withInput($request->except(['images', 'files']))
                ->withErrors(['images' => 'Maximum 2 images allowed.']);
        }
        if (count($files) > 3) {
            return back()->withInput($request->except(['images', 'files']))
                ->withErrors(['files' => 'Maximum 3 files allowed.']);
        }

        $case = DB::transaction(function () use ($data, $images, $files, $request) {
            $case = LegalCase::query()->create($data);
            $this->storeAttachments($case, $images, 'image');
            $this->storeAttachments($case, $files, 'file');

            return $case;
        });

        return redirect()->route('cases.index')->with('success', 'Case created'.(
            (count($images) + count($files)) > 0 ? ' with attachments.' : '.'
        ));
    }

    public function show(LegalCase $legal_case): RedirectResponse
    {
        $this->authorize('view', $legal_case);

        return redirect()->route('cases.index', ['details' => $legal_case->id]);
    }

    public function edit(LegalCase $legal_case): RedirectResponse
    {
        $this->authorize('update', $legal_case);

        return redirect()->route('cases.index', ['open' => 'edit-case-'.$legal_case->id]);
    }

    public function update(Request $request, LegalCase $legal_case): RedirectResponse
    {
        $this->authorize('update', $legal_case);

        $data = $this->validated($request);
        $restrictedLawyerId = app(FirmContext::class)->restrictedLawyerId();
        if ($restrictedLawyerId !== null) {
            $data['lawyer_id'] = $restrictedLawyerId;
        }

        $legal_case->update($data);

        return redirect()->route('cases.index')->with('success', 'Case updated.');
    }

    public function updateNotes(Request $request, LegalCase $legal_case): RedirectResponse
    {
        $this->authorize('update', $legal_case);

        $data = $request->validate([
            'description' => ['nullable', 'string'],
        ]);

        $legal_case->update([
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('cases.index')->with('success', 'Case notes saved.');
    }

    public function destroy(LegalCase $legal_case): RedirectResponse
    {
        $this->authorize('delete', $legal_case);

        $legal_case->delete();

        return back()->with('success', 'Case deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'case_number' => ['nullable', 'string', 'max:64'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'lawyer_id' => ['nullable', 'exists:lawyers,id'],
            'status' => ['required', 'in:open,in_progress,on_hold,closed'],
            'description' => ['nullable', 'string'],
            'opened_on' => ['nullable', 'date'],
            'images' => ['nullable', 'array', 'max:2'],
            'images.*' => ['image', 'max:10240'],
            'files' => ['nullable', 'array', 'max:3'],
            'files.*' => ['file', 'max:20480', 'mimes:pdf,doc,docx,txt,xls,xlsx,csv,zip,rtf'],
        ], [
            'images.max' => 'Maximum 2 images allowed.',
            'files.max' => 'Maximum 3 files allowed.',
            'images.*.image' => 'Only image files are allowed in Images.',
            'files.*.mimes' => 'Files must be PDF, Word, Excel, text, or ZIP.',
        ]);
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedList(Request $request, string $key): array
    {
        $raw = $request->file($key);
        if (! $raw) {
            return [];
        }

        $list = is_array($raw) ? $raw : [$raw];

        return array_values(array_filter($list, fn ($file) => $file instanceof UploadedFile && $file->isValid()));
    }

    /**
     * @param  list<UploadedFile>  $uploads
     */
    private function storeAttachments(LegalCase $case, array $uploads, string $kind): void
    {
        if ($uploads === []) {
            return;
        }

        $firmId = (int) ($case->firm_id ?: auth()->user()?->firm_id ?: 0);
        $directory = 'crm-documents/'.$firmId.'/cases/'.$case->id;
        Storage::disk('local')->makeDirectory($directory);

        foreach ($uploads as $file) {
            $path = $file->store($directory, 'local');
            if (! $path) {
                continue;
            }

            CrmDocument::query()->create([
                'client_id' => $case->client_id,
                'legal_case_id' => $case->id,
                'uploaded_by' => auth()->id(),
                'title' => ($kind === 'image' ? 'Case image' : 'Case file').' — '.$file->getClientOriginalName(),
                'original_name' => $file->getClientOriginalName(),
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'size_bytes' => $file->getSize() ?: 0,
                'status' => 'open',
                'notes' => $kind,
            ]);
        }
    }
}
