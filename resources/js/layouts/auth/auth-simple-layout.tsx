import { Link } from "@inertiajs/react";
import { BookOpen } from "lucide-react";
import { home } from "@/routes";
import type { AuthLayoutProps } from "@/types";

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh items-center justify-center overflow-hidden bg-stone-950 p-6 text-stone-100 md:p-10">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(168,85,247,0.22),transparent_38%),radial-gradient(circle_at_bottom_right,rgba(244,114,182,0.14),transparent_36%)]" />
            <div className="relative w-full max-w-md rounded-3xl border border-white/10 bg-stone-900/80 p-7 shadow-2xl shadow-black/40 backdrop-blur md:p-10">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-3 font-medium"
                        >
                            <div className="flex size-12 items-center justify-center rounded-2xl bg-violet-500 text-white shadow-lg shadow-violet-950/40">
                                <BookOpen
                                    className="size-6"
                                    aria-hidden="true"
                                />
                            </div>
                            <span className="text-lg font-semibold tracking-wide text-white">
                                Nora
                            </span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-2xl font-semibold tracking-tight text-white">
                                {title}
                            </h1>
                            <p className="text-center text-sm leading-6 text-stone-400">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
