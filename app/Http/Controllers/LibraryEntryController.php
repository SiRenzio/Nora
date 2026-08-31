<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLibraryEntryRequest;
use App\Http\Requests\UpdateLibraryEntryRequest;
use App\Models\LibraryEntry;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LibraryEntryController extends Controller
{
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
