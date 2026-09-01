import { Form, Head, Link } from "@inertiajs/react";
import {
    Archive,
    BookOpen,
    Check,
    ExternalLink,
    Plus,
    Search,
    X,
} from "lucide-react";
import { useState } from "react";
import InputError from "@/components/input-error";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { destroy, importMethod, index, store, update } from "@/routes/library";
import { update as updateProgress } from "@/routes/library/progress";

type Entry = {
    id: number;
    source_url: string | null;
    source_website: string | null;
    status: string;
    latest_chapter: string | null;
    last_completed_chapter: string | null;
    last_read_at: string | null;
    monitoring_enabled: boolean;
    notes: string | null;
    rating: number | null;
    unread_count: number | null;
    next_chapter: string | null;
    next_chapter_url: string | null;
    title: {
        title: string;
        alternative_title: string | null;
        content_type: string;
        cover_url: string | null;
        description: string | null;
    };
};

type Props = {
    entries: {
        data: Entry[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
    filters: { search?: string; status?: string; content_type?: string };
};

const statuses = [
    ["plan_to_read", "Plan to Read"],
    ["reading", "Reading"],
    ["on_hold", "On Hold"],
    ["completed", "Completed"],
    ["dropped", "Dropped"],
] as const;
const contentTypes = [
    ["manga", "Manga"],
    ["manhwa", "Manhwa"],
    ["manhua", "Manhua"],
    ["comic", "Comic"],
    ["novel", "Novel"],
] as const;

function chapterOptions(entry: Entry): string[] {
    const latestMatch = entry.latest_chapter?.match(/(\d+(?:\.\d+)?)\s*$/);
    const latest = latestMatch ? Number(latestMatch[1]) : 0;

    if (!Number.isFinite(latest) || latest <= 0) {
        return entry.last_completed_chapter
            ? [entry.last_completed_chapter]
            : [];
    }

    const options = Array.from(
        { length: Math.floor(latest) },
        (_, index) => `Chapter ${index + 1}`,
    );

    if (!Number.isInteger(latest)) {
        options.push(`Chapter ${latest}`);
    }

    if (
        entry.last_completed_chapter &&
        !options.includes(entry.last_completed_chapter)
    ) {
        options.push(entry.last_completed_chapter);
    }

    return options;
}

function EntryFields({ entry }: { entry?: Entry }) {
    return (
        <div className="grid gap-4 md:grid-cols-2">
            <div className="grid gap-2 md:col-span-2">
                <Label>Title</Label>
                <Input
                    name="title"
                    defaultValue={entry?.title.title}
                    required
                    maxLength={255}
                    placeholder="Title of the story"
                />
            </div>
            <div className="grid gap-2">
                <Label>Alternative title</Label>
                <Input
                    name="alternative_title"
                    defaultValue={entry?.title.alternative_title ?? ""}
                    maxLength={255}
                />
            </div>
            <div className="grid gap-2">
                <Label>Content type</Label>
                <select
                    name="content_type"
                    defaultValue={entry?.title.content_type ?? "manga"}
                    className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                >
                    {contentTypes.map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </select>
            </div>
            <div className="grid gap-2">
                <Label>Reading status</Label>
                <select
                    name="status"
                    defaultValue={entry?.status ?? "plan_to_read"}
                    className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                >
                    {statuses.map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </select>
            </div>
            <div className="grid gap-2">
                <Label>Source website</Label>
                <Input
                    name="source_website"
                    defaultValue={entry?.source_website ?? ""}
                    placeholder="e.g. Webtoon"
                />
            </div>
            <div className="grid gap-2 md:col-span-2">
                <Label>Original source URL</Label>
                <Input
                    name="source_url"
                    type="url"
                    defaultValue={entry?.source_url ?? ""}
                    placeholder="https://..."
                />
            </div>
            <div className="grid gap-2 md:col-span-2">
                <Label>Cover image URL</Label>
                <Input
                    name="cover_url"
                    type="url"
                    defaultValue={entry?.title.cover_url ?? ""}
                    placeholder="https://..."
                />
            </div>
            <div className="grid gap-2">
                <Label>Latest available chapter</Label>
                <Input
                    name="latest_chapter"
                    defaultValue={entry?.latest_chapter ?? ""}
                    placeholder="e.g. Chapter 24.5"
                    maxLength={100}
                />
            </div>
            <div className="grid gap-2">
                <Label>Last completed chapter</Label>
                <Input
                    name="last_completed_chapter"
                    defaultValue={entry?.last_completed_chapter ?? ""}
                    placeholder="e.g. Side Story 3"
                    maxLength={100}
                />
            </div>
            <div className="grid gap-2">
                <Label>Last read</Label>
                <Input
                    name="last_read_at"
                    type="datetime-local"
                    defaultValue={entry?.last_read_at?.slice(0, 16) ?? ""}
                />
            </div>
            <div className="grid gap-2">
                <Label>Rating (1–10)</Label>
                <Input
                    name="rating"
                    type="number"
                    min={1}
                    max={10}
                    defaultValue={entry?.rating ?? ""}
                />
            </div>
            <div className="grid gap-2 md:col-span-2">
                <Label>Description</Label>
                <textarea
                    name="description"
                    defaultValue={entry?.title.description ?? ""}
                    rows={3}
                    maxLength={5000}
                    className="border-input bg-background rounded-md border px-3 py-2 text-sm"
                />
            </div>
            <div className="grid gap-2 md:col-span-2">
                <Label>Personal notes</Label>
                <textarea
                    name="notes"
                    defaultValue={entry?.notes ?? ""}
                    rows={3}
                    maxLength={5000}
                    className="border-input bg-background rounded-md border px-3 py-2 text-sm"
                />
            </div>
            <div className="flex items-center gap-3 md:col-span-2">
                <input type="hidden" name="monitoring_enabled" value="0" />
                <input
                    id={`monitoring-${entry?.id ?? "new"}`}
                    type="checkbox"
                    name="monitoring_enabled"
                    value="1"
                    defaultChecked={entry?.monitoring_enabled ?? false}
                    className="size-4"
                />
                <Label htmlFor={`monitoring-${entry?.id ?? "new"}`}>
                    Enable monitoring when this source becomes supported
                </Label>
            </div>
        </div>
    );
}

export default function LibraryIndex({ entries, filters }: Props) {
    const [showAddForm, setShowAddForm] = useState(false);

    return (
        <>
            <Head title="My Library" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-muted-foreground text-sm">
                            {entries.total} saved titles
                        </p>
                        <h1 className="text-3xl font-semibold tracking-tight">
                            My Library
                        </h1>
                    </div>
                    <Button
                        type="button"
                        onClick={() => setShowAddForm((visible) => !visible)}
                        aria-expanded={showAddForm}
                        aria-controls="add-library-entry"
                    >
                        {showAddForm ? (
                            <X className="size-4" />
                        ) : (
                            <Plus className="size-4" />
                        )}
                        {showAddForm ? "Close form" : "Add title"}
                    </Button>
                </div>
                <div
                    className={`grid transition-[grid-template-rows,opacity,margin] duration-300 ease-out motion-reduce:transition-none ${
                        showAddForm
                            ? "my-0 grid-rows-[1fr] opacity-100"
                            : "pointer-events-none -my-3 grid-rows-[0fr] opacity-0"
                    }`}
                >
                    <div className="overflow-hidden">
                        <section
                            id="add-library-entry"
                            aria-hidden={!showAddForm}
                            className={`bg-card rounded-2xl border p-5 shadow-sm transition-transform duration-300 ease-out motion-reduce:transition-none md:p-6 ${
                                showAddForm ? "translate-y-0" : "-translate-y-4"
                            }`}
                        >
                            <Form
                                {...importMethod.form()}
                                onSuccess={() => setShowAddForm(false)}
                                className="mb-6 space-y-4 border-b pb-6"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div>
                                            <h2 className="text-xl font-semibold">
                                                Import from a website
                                            </h2>
                                            <p className="text-muted-foreground text-sm">
                                                Automatic import supports Asura
                                                Scans and Genz Toons. Comix URLs
                                                can be saved with the manual
                                                form below for now.
                                            </p>
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="grid gap-2 md:col-span-2">
                                                <Label htmlFor="import-source-url">
                                                    Series page URL
                                                </Label>
                                                <Input
                                                    id="import-source-url"
                                                    name="source_url"
                                                    type="url"
                                                    required
                                                    placeholder="https://asurascans.com/comics/..."
                                                />
                                                {errors.source_url && (
                                                    <InputError
                                                        message={
                                                            errors.source_url
                                                        }
                                                    />
                                                )}
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Reading status</Label>
                                                <select
                                                    name="status"
                                                    defaultValue="plan_to_read"
                                                    className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                                                >
                                                    {statuses.map(
                                                        ([value, label]) => (
                                                            <option
                                                                key={value}
                                                                value={value}
                                                            >
                                                                {label}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>
                                                    Last completed chapter
                                                </Label>
                                                <Input
                                                    name="last_completed_chapter"
                                                    maxLength={100}
                                                    placeholder="e.g. Chapter 12"
                                                />
                                            </div>
                                            <div className="flex items-center gap-3 md:col-span-2">
                                                <input
                                                    type="hidden"
                                                    name="monitoring_enabled"
                                                    value="0"
                                                />
                                                <input
                                                    id="import-monitoring"
                                                    type="checkbox"
                                                    name="monitoring_enabled"
                                                    value="1"
                                                    defaultChecked
                                                    className="size-4"
                                                />
                                                <Label htmlFor="import-monitoring">
                                                    Monitor this title for new
                                                    chapters
                                                </Label>
                                            </div>
                                        </div>
                                        <Button disabled={processing}>
                                            {processing
                                                ? "Importing..."
                                                : "Import title"}
                                        </Button>
                                    </>
                                )}
                            </Form>
                            <Form
                                {...store.form()}
                                onSuccess={() => setShowAddForm(false)}
                                className="space-y-5"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div>
                                            <h2 className="text-xl font-semibold">
                                                Add a title manually
                                            </h2>
                                            <p className="text-muted-foreground text-sm">
                                                Save a story even when its
                                                website is not supported yet.
                                            </p>
                                        </div>
                                        <EntryFields />
                                        {Object.values(errors)[0] && (
                                            <InputError
                                                message={
                                                    Object.values(errors)[0]
                                                }
                                            />
                                        )}
                                        <div className="flex flex-wrap gap-3">
                                            <Button disabled={processing}>
                                                Save to library
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() =>
                                                    setShowAddForm(false)
                                                }
                                            >
                                                Cancel
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>
                    </div>
                </div>
                <Form
                    {...index.form()}
                    className="grid gap-3 rounded-xl border p-4 sm:grid-cols-[1fr_180px_180px_auto]"
                >
                    <div className="relative">
                        <Search className="text-muted-foreground absolute top-2.5 left-3 size-4" />
                        <Input
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Search titles"
                            className="pl-9"
                        />
                    </div>
                    <select
                        name="status"
                        defaultValue={filters.status ?? ""}
                        className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                    >
                        <option value="">All statuses</option>
                        {statuses.map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                    <select
                        name="content_type"
                        defaultValue={filters.content_type ?? ""}
                        className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                    >
                        <option value="">All types</option>
                        {contentTypes.map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                    <Button variant="secondary">Filter</Button>
                </Form>
                {entries.data.length === 0 ? (
                    <div className="flex min-h-72 flex-col items-center justify-center rounded-2xl border border-dashed text-center">
                        <BookOpen className="text-muted-foreground mb-4 size-10" />
                        <h2 className="text-lg font-semibold">
                            No titles found
                        </h2>
                        <p className="text-muted-foreground max-w-sm text-sm">
                            Add your first manga, manhwa, comic, or novel to
                            begin tracking it.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {entries.data.map((entry) => (
                            <article
                                key={entry.id}
                                className="bg-card overflow-hidden rounded-2xl border shadow-sm"
                            >
                                <div className="flex gap-4 p-4">
                                    <div className="bg-muted relative flex h-32 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg">
                                        <BookOpen className="text-muted-foreground size-8" />
                                        {entry.title.cover_url && (
                                            <img
                                                src={entry.title.cover_url}
                                                alt={`${entry.title.title} cover`}
                                                className="absolute inset-0 h-full w-full object-cover"
                                                onError={(event) => {
                                                    event.currentTarget.hidden = true;
                                                }}
                                            />
                                        )}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="mb-2 flex flex-wrap gap-2 text-xs">
                                            <span className="rounded-full bg-violet-500/15 px-2 py-1 text-violet-700 dark:text-violet-300">
                                                {
                                                    contentTypes.find(
                                                        ([v]) =>
                                                            v ===
                                                            entry.title
                                                                .content_type,
                                                    )?.[1]
                                                }
                                            </span>
                                            <span className="bg-muted rounded-full px-2 py-1">
                                                {
                                                    statuses.find(
                                                        ([v]) =>
                                                            v === entry.status,
                                                    )?.[1]
                                                }
                                            </span>
                                            {entry.unread_count !== null &&
                                                entry.unread_count > 0 && (
                                                    <span className="rounded-full bg-amber-500/15 px-2 py-1 text-amber-700 dark:text-amber-300">
                                                        {entry.unread_count}{" "}
                                                        unread
                                                    </span>
                                                )}
                                        </div>
                                        <h2 className="line-clamp-2 font-semibold">
                                            {entry.title.title}
                                        </h2>
                                        {entry.title.alternative_title && (
                                            <p className="text-muted-foreground mt-1 truncate text-xs">
                                                {entry.title.alternative_title}
                                            </p>
                                        )}
                                        <dl className="mt-3 grid gap-1 text-xs">
                                            <div>
                                                <dt className="text-muted-foreground inline">
                                                    Latest:{" "}
                                                </dt>
                                                <dd className="inline">
                                                    {entry.latest_chapter ||
                                                        "Unknown"}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-muted-foreground inline">
                                                    Read:{" "}
                                                </dt>
                                                <dd className="inline">
                                                    {entry.last_completed_chapter ||
                                                        "Not started"}
                                                </dd>
                                            </div>
                                        </dl>
                                        {entry.source_url && (
                                            <a
                                                href={entry.source_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="mt-3 inline-flex items-center gap-1 text-xs font-medium text-violet-600 hover:underline"
                                            >
                                                Continue reading{" "}
                                                <ExternalLink className="size-3" />
                                            </a>
                                        )}
                                    </div>
                                </div>
                                <div className="border-t px-4 py-3">
                                    <Form
                                        {...updateProgress.form(entry.id)}
                                        className="space-y-2"
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <div className="flex gap-2">
                                                    <select
                                                        name="chapter"
                                                        aria-label="Completed chapter"
                                                        defaultValue=""
                                                        className="border-input bg-background h-8 min-w-0 flex-1 rounded-md border px-3 text-xs"
                                                        required
                                                    >
                                                        <option
                                                            value=""
                                                            disabled
                                                        >
                                                            Select chapter
                                                        </option>
                                                        {chapterOptions(
                                                            entry,
                                                        ).map((chapter) => (
                                                            <option
                                                                key={chapter}
                                                                value={chapter}
                                                            >
                                                                {chapter}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <Button
                                                        name="progress_action"
                                                        value="manual"
                                                        variant="secondary"
                                                        size="sm"
                                                        disabled={
                                                            processing ||
                                                            chapterOptions(
                                                                entry,
                                                            ).length === 0
                                                        }
                                                    >
                                                        <Check className="size-3.5" />
                                                        Mark read
                                                    </Button>
                                                </div>
                                                <div className="flex flex-wrap gap-2">
                                                    {entry.next_chapter_url ? (
                                                        <Button
                                                            asChild
                                                            variant="outline"
                                                            size="sm"
                                                        >
                                                            <a
                                                                href={
                                                                    entry.next_chapter_url
                                                                }
                                                                target="_blank"
                                                                rel="noreferrer"
                                                            >
                                                                Read next
                                                                {entry.next_chapter &&
                                                                    ` (${entry.next_chapter})`}
                                                                <ExternalLink className="size-3" />
                                                            </a>
                                                        </Button>
                                                    ) : (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            disabled
                                                        >
                                                            Read next
                                                        </Button>
                                                    )}
                                                    <Button
                                                        name="progress_action"
                                                        value="latest"
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={
                                                            processing ||
                                                            !entry.latest_chapter ||
                                                            entry.unread_count ===
                                                                0
                                                        }
                                                    >
                                                        Mark latest read
                                                    </Button>
                                                </div>
                                                {errors.chapter && (
                                                    <InputError
                                                        message={errors.chapter}
                                                    />
                                                )}
                                            </>
                                        )}
                                    </Form>
                                </div>
                                <div className="border-t px-4 py-3">
                                    <div className="relative">
                                        <details>
                                            <summary className="w-fit cursor-pointer py-2 text-sm font-medium">
                                                Edit details
                                            </summary>
                                            <Form
                                                {...update.form(entry.id)}
                                                className="mt-4 space-y-4"
                                            >
                                                {({ processing, errors }) => (
                                                    <>
                                                        <EntryFields
                                                            entry={entry}
                                                        />
                                                        {Object.values(
                                                            errors,
                                                        )[0] && (
                                                            <InputError
                                                                message={
                                                                    Object.values(
                                                                        errors,
                                                                    )[0]
                                                                }
                                                            />
                                                        )}
                                                        <Button
                                                            size="sm"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            Save changes
                                                        </Button>
                                                    </>
                                                )}
                                            </Form>
                                        </details>
                                        <Form
                                            {...destroy.form(entry.id)}
                                            className="absolute top-0 right-0"
                                        >
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-muted-foreground hover:text-destructive"
                                            >
                                                <Archive className="size-4" />{" "}
                                                Archive
                                            </Button>
                                        </Form>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
                {entries.links.length > 3 && (
                    <nav
                        className="flex flex-wrap justify-center gap-2"
                        aria-label="Library pages"
                    >
                        {entries.links.map((link) =>
                            link.url ? (
                                <Link
                                    key={link.label}
                                    href={link.url}
                                    className={`rounded-md border px-3 py-1.5 text-sm ${link.active ? "bg-primary text-primary-foreground" : ""}`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <span
                                    key={link.label}
                                    className="text-muted-foreground rounded-md border px-3 py-1.5 text-sm opacity-50"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}

LibraryIndex.layout = { breadcrumbs: [{ title: "My Library", href: index() }] };
