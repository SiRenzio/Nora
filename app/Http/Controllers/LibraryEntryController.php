<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportLibraryEntryRequest;
use App\Http\Requests\StoreLibraryEntryRequest;
use App\Http\Requests\UpdateLibraryEntryRequest;
use App\Http\Requests\UpdateReadingProgressRequest;
use App\Models\LibraryEntry;
use App\Models\Title;
use App\Services\Sources\SourceImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LibraryEntryController extends Controller
{
    public function import(ImportLibraryEntryRequest $request, SourceImporter $importer): RedirectResponse
    {
        $data = $request->validated();
        $url = rtrim($data['source_url'], '/');

        $existingEntry = $request->user()->libraryEntries()->with('title')->where('source_url', $url)->first();
        $imported = $importer->import($url);
        DB::transaction(function () use ($request, $data, $imported, $existingEntry) {
            if ($existingEntry) {
                $existingEntry->title->update([
                    'title' => $imported->title,
                    'content_type' => $imported->contentType,
                    'cover_url' => $imported->coverUrl,
                    'description' => $imported->description,
                ]);
                $existingEntry->update([
                    'source_website' => $imported->sourceWebsite,
                    'latest_chapter' => $imported->latestChapter,
                    'chapter_urls' => $imported->chapterUrls,
                    'last_checked_at' => now(),
                    'monitoring_enabled' => $data['monitoring_enabled'],
                    'archived_at' => null,
                ]);

                return;
            }

            $title = Title::create([
                'title' => $imported->title,
                'content_type' => $imported->contentType,
                'cover_url' => $imported->coverUrl,
                'description' => $imported->description,
                'created_by_user_id' => $request->user()->id,
            ]);
            $request->user()->libraryEntries()->create([
                'title_id' => $title->id,
                'source_url' => $imported->sourceUrl,
                'source_website' => $imported->sourceWebsite,
                'status' => $data['status'],
                'latest_chapter' => $imported->latestChapter,
                'chapter_urls' => $imported->chapterUrls,
                'last_completed_chapter' => $data['last_completed_chapter'] ?? null,
                'last_checked_at' => now(),
                'monitoring_enabled' => $data['monitoring_enabled'],
            ]);
        });

        $message = $existingEntry ? 'Title metadata and chapters refreshed.' : 'Title imported to your library.';

        return to_route('library.index')->with('success', $message);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string'],
            'content_type' => ['nullable', 'string'],
        ]);

        $entries = $request->user()->libraryEntries()
            ->with('title')
            ->whereNull('archived_at')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'title',
                fn ($titleQuery) => $titleQuery
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('alternative_title', 'like', "%{$search}%"),
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['content_type'] ?? null, fn ($query, $type) => $query->whereHas(
                'title',
                fn ($titleQuery) => $titleQuery->where('content_type', $type),
            ))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('library/index', [
            'entries' => $entries,
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLibraryEntryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $title = Title::create([
                ...Arr::only($data, ['title', 'alternative_title', 'content_type', 'cover_url', 'description']),
                'created_by_user_id' => $request->user()->id,
            ]);

            $request->user()->libraryEntries()->create([
                ...Arr::except($data, ['title', 'alternative_title', 'content_type', 'cover_url', 'description']),
                'title_id' => $title->id,
            ]);
        });

        return to_route('library.index')->with('success', 'Title added to your library.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLibraryEntryRequest $request, LibraryEntry $libraryEntry): RedirectResponse
    {
        DB::transaction(function () use ($request, $libraryEntry) {
            $data = $request->validated();
            $libraryEntry->title->update(Arr::only(
                $data,
                ['title', 'alternative_title', 'content_type', 'cover_url', 'description'],
            ));
            $libraryEntry->update(Arr::except(
                $data,
                ['title', 'alternative_title', 'content_type', 'cover_url', 'description'],
            ));
        });

        return to_route('library.index')->with('success', 'Library entry updated.');
    }

    public function updateProgress(
        UpdateReadingProgressRequest $request,
        LibraryEntry $libraryEntry,
    ): RedirectResponse {
        $action = $request->validated('progress_action');
        $chapter = match ($action) {
            'manual' => $request->validated('chapter'),
            'next' => $libraryEntry->next_chapter,
            'latest' => $libraryEntry->latest_chapter,
        };

        if ($chapter === null) {
            throw ValidationException::withMessages([
                'chapter' => 'This chapter label cannot be advanced automatically. Enter the chapter manually.',
            ]);
        }

        $libraryEntry->update([
            'last_completed_chapter' => $chapter,
            'last_read_at' => now(),
            'status' => $libraryEntry->status === 'plan_to_read' ? 'reading' : $libraryEntry->status,
        ]);

        return to_route('library.index')->with('success', "Progress updated to {$chapter}.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, LibraryEntry $libraryEntry): RedirectResponse
    {
        abort_unless($request->user()->is($libraryEntry->user), 403);

        $libraryEntry->update(['archived_at' => now()]);

        return to_route('library.index')->with('success', 'Title archived.');
    }
}
