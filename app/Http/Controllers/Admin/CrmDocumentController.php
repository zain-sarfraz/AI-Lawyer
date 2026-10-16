<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CrmDocument;
use App\Models\Lead;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmDocumentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:documents.view', only: ['index', 'download', 'preview']),
            new Middleware('permission:documents.manage', only: ['store', 'destroy', 'update']),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CrmDocument::class);

        $documents = CrmDocument::query()
            ->with(['client', 'lead', 'legalCase.client', 'uploader'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('original_name', 'like', $term)
                        ->orWhere('status', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->client_id))
            ->latest()
            ->limit(500)
            ->get();

        $groups = $this->groupDocuments($documents);
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = 20;
        $slice = $groups->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($slice, $groups->count(), $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('crm.documents.index', [
            'groups' => $paginator,
            'filters' => $request->only('q', 'client_id', 'status'),
            'formClients' => Client::query()->orderBy('name')->get(['id', 'name', 'client_number']),
            'formLeads' => Lead::query()->latest()->limit(100)->get(['id', 'name', 'phone']),
            'formCases' => LegalCase::query()->latest()->limit(100)->get(['id', 'title', 'case_number']),
        ]);
    }

    /**
     * @param  Collection<int, CrmDocument>  $documents
     * @return Collection<int, array<string, mixed>>
     */
    private function groupDocuments(Collection $documents): Collection
    {
        return $documents
            ->groupBy(function (CrmDocument $doc) {
                if ($doc->client_id) {
                    return 'client:'.$doc->client_id;
                }
                if ($doc->legalCase?->client_id) {
                    return 'client:'.$doc->legalCase->client_id;
                }
                if ($doc->legal_case_id) {
                    return 'case:'.$doc->legal_case_id;
                }
                if ($doc->lead_id) {
                    return 'lead:'.$doc->lead_id;
                }

                return 'doc:'.$doc->id;
            })
            ->map(function (Collection $items) {
                /** @var CrmDocument $first */
                $first = $items->first();
                $clientName = $first->client?->name
                    ?: $first->legalCase?->client?->name
                    ?: $first->lead?->name
                    ?: $first->lead?->phone
                    ?: ($first->legalCase?->title ? 'Case: '.$first->legalCase->title : 'Unassigned');

                $files = $items->map(function (CrmDocument $doc) {
                    $mime = (string) ($doc->mime_type ?: '');
                    $isImage = str_starts_with($mime, 'image/')
                        || preg_match('/\.(png|jpe?g|gif|webp|bmp)$/i', (string) $doc->original_name);

                    return [
                        'id' => $doc->id,
                        'title' => $doc->title,
                        'name' => $doc->original_name,
                        'size' => $doc->humanSize(),
                        'status' => $doc->status ?: 'open',
                        'case' => $doc->legalCase?->case_number ?: ($doc->legalCase?->title ?: '—'),
                        'is_image' => (bool) $isImage,
                        'preview_url' => $isImage ? route('crm.documents.preview', $doc) : null,
                        'download_url' => route('crm.documents.download', $doc),
                        'created_at' => $doc->created_at?->format('Y-m-d'),
                    ];
                })->values()->all();

                $imageCount = collect($files)->where('is_image', true)->count();
                $fileCount = count($files) - $imageCount;
                $cases = collect($files)->pluck('case')->unique()->filter(fn ($c) => $c && $c !== '—')->values()->all();
                $statuses = collect($files)->pluck('status')->unique()->values()->all();

                return [
                    'client' => $clientName,
                    'files_count' => count($files),
                    'image_count' => $imageCount,
                    'file_count' => $fileCount,
                    'cases' => $cases,
                    'status' => in_array('open', $statuses, true) ? 'open' : ($statuses[0] ?? 'open'),
                    'uploaded' => $items->max('created_at')?->format('Y-m-d') ?: '—',
                    'search' => strtolower(trim($clientName.' '.collect($files)->pluck('name')->implode(' ').implode(' ', $cases))),
                    'gallery' => [
                        'client' => $clientName,
                        'files' => $files,
                    ],
                ];
            })
            ->sortBy('client', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CrmDocument::class);

        if ($uploadError = $this->uploadErrorMessage($request)) {
            return back()
                ->withInput($request->except(['file', 'files']))
                ->withErrors(['files' => $uploadError, 'file' => $uploadError]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:20480'],
            'file' => ['nullable', 'file', 'max:20480'],
            'status' => ['nullable', 'string', 'in:open,closed'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'legal_case_id' => ['nullable', 'exists:legal_cases,id'],
            'notes' => ['nullable', 'string'],
        ], [
            'files.*.file' => 'One or more selected files could not be uploaded. Try files under 20 MB.',
            'files.*.max' => 'Each file may not be greater than 20 MB.',
            'file.file' => 'The selected file could not be uploaded. Try a file under 20 MB.',
            'file.max' => 'The file may not be greater than 20 MB.',
        ]);

        $rawFiles = $request->file('files');
        $uploadedFiles = is_array($rawFiles) ? $rawFiles : ($request->hasFile('file') ? [$request->file('file')] : ($rawFiles ? [$rawFiles] : []));

        if (empty($uploadedFiles)) {
            return back()
                ->withInput($request->except(['file', 'files']))
                ->withErrors(['files' => 'Please select at least one file to upload.']);
        }

        $firmId = (int) (auth()->user()->firm_id ?: 0);
        $directory = 'crm-documents/'.$firmId;
        Storage::disk('local')->makeDirectory($directory);

        $status = in_array($request->input('status'), ['open', 'closed'], true) ? $request->input('status') : 'open';
        $count = count($uploadedFiles);
        $savedCount = 0;

        foreach ($uploadedFiles as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $path = $file->store($directory, 'local');
            if (! $path) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $title = $count > 1 ? ($data['title'].' - '.$originalName) : $data['title'];

            CrmDocument::query()->create([
                'client_id' => $data['client_id'] ?? null,
                'lead_id' => $data['lead_id'] ?? null,
                'legal_case_id' => $data['legal_case_id'] ?? null,
                'uploaded_by' => auth()->id(),
                'title' => $title,
                'original_name' => $originalName,
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'size_bytes' => $file->getSize() ?: 0,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);
            $savedCount++;
        }

        if ($savedCount === 0) {
            return back()
                ->withInput($request->except(['file', 'files']))
                ->withErrors(['files' => 'Could not save files to storage. Check folder permissions.']);
        }

        $message = $savedCount > 1 ? "{$savedCount} documents uploaded." : 'Document uploaded.';
        return redirect()->route('crm.documents.index')->with('success', $message);
    }

    public function update(Request $request, CrmDocument $crm_document): RedirectResponse
    {
        $this->authorize('update', $crm_document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:open,closed'],
            'notes' => ['nullable', 'string'],
        ]);

        $crm_document->update([
            'title' => $data['title'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Document updated successfully.');
    }

    private function uploadErrorMessage(Request $request): ?string
    {
        $hasFiles = $request->hasFile('files') || $request->hasFile('file');
        if (! $hasFiles) {
            // PHP drops the file entirely when it exceeds post/upload limits.
            $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
            $postMax = $this->iniBytes((string) ini_get('post_max_size'));
            if ($contentLength > 0 && $postMax > 0 && $contentLength > $postMax) {
                return 'Files are too large for the server (post_max_size is '.ini_get('post_max_size').'). Use smaller files.';
            }

            if (! $request->file('files') && ! $request->file('file')) {
                return 'Please choose at least one file to upload.';
            }
        }

        $files = $request->file('files') ?? ($request->hasFile('file') ? [$request->file('file')] : []);
        if (! is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file && ! $file->isValid()) {
                return match ($file->getError()) {
                    UPLOAD_ERR_OK => null,
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large. Server upload limit is '.ini_get('upload_max_filesize').'. Try a smaller file.',
                    UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try again.',
                    UPLOAD_ERR_NO_FILE => 'Please choose at least one file to upload.',
                    UPLOAD_ERR_NO_TMP_DIR => 'PHP temp folder is not available. Restart the server and try again.',
                    UPLOAD_ERR_CANT_WRITE => 'PHP could not write the uploaded file to temp storage.',
                    default => 'A file failed to upload (error '.$file->getError().'). Please try again.',
                };
            }
        }

        return null;
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    public function download(CrmDocument $crm_document): StreamedResponse
    {
        $this->authorize('view', $crm_document);

        $disk = Storage::disk($crm_document->disk ?: 'local');
        abort_unless($disk->exists($crm_document->path), 404);

        return $disk->download($crm_document->path, $crm_document->original_name);
    }

    public function preview(CrmDocument $crm_document)
    {
        $this->authorize('view', $crm_document);

        $disk = Storage::disk($crm_document->disk ?: 'local');
        abort_unless($disk->exists($crm_document->path), 404);

        $mime = $crm_document->mime_type ?: 'application/octet-stream';

        return response()->file($disk->path($crm_document->path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$crm_document->original_name.'"',
        ]);
    }

    public function destroy(CrmDocument $crm_document): RedirectResponse
    {
        $this->authorize('delete', $crm_document);

        $crm_document->deleteFile();
        $crm_document->delete();

        return back()->with('success', 'Document deleted.');
    }
}
