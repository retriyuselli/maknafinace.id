@extends('layouts.app')

@section('title', $currentArticle->title.' — Dokumentasi WOFINS')

@push('styles')
<style>
        :root {
            --wf-navy: #0b1f3a;
            --wf-navy-deep: #071526;
            --wf-gold: #c9a227;
            --wf-gold-soft: #e8d48b;
            --wf-cream: #f7f4ee;
            --wf-ink: #1a2332;
            --wf-muted: #5c6675;
            --wf-line: #e6e2d9;
        }

        .wf-page {
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--wf-ink);
            background: #fff;
        }

        .wf-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--wf-line);
        }

        .wf-btn-navy {
            background: var(--wf-navy);
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
        }

        .wf-btn-ghost {
            border: 1.5px solid var(--wf-navy);
            color: var(--wf-navy);
            border-radius: 999px;
            font-weight: 700;
            background: #fff;
        }

        .documentation-content h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--wf-navy);
        }
        .documentation-content h3 {
            font-size: 1.05rem;
            font-weight: 600;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--wf-ink);
        }
        .documentation-content p {
            font-size: 0.95rem;
            margin-bottom: 0.85rem;
            line-height: 1.7;
            color: var(--wf-muted);
        }
        .documentation-content ul {
            list-style-type: disc;
            padding-left: 1.25rem;
            margin-bottom: 0.85rem;
        }
        .documentation-content ol {
            list-style-type: decimal;
            padding-left: 1.25rem;
            margin-bottom: 0.85rem;
        }
        .documentation-content li {
            margin-bottom: 0.35rem;
            color: var(--wf-muted);
            font-size: 0.95rem;
        }
        .documentation-content pre {
            background-color: #f3f4f6;
            padding: 0.85rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin-bottom: 0.85rem;
            font-size: 0.8rem;
        }
        .documentation-content blockquote {
            border-left: 3px solid var(--wf-gold);
            padding-left: 0.85rem;
            margin: 0 0 0.85rem;
            font-style: italic;
            color: var(--wf-muted);
        }
        .documentation-content a {
            color: var(--wf-navy);
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .documentation-content strong {
            font-weight: 600;
            color: var(--wf-ink);
        }

        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('content')
    <div class="wf-page">
        @include('front.partials.wf-nav')

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <nav class="text-sm text-[var(--wf-muted)] mb-6">
                <a href="{{ route('home') }}" class="hover:text-[var(--wf-navy)]">Beranda</a>
                <span class="mx-2">/</span>
                <a href="{{ route('docs.index') }}" class="hover:text-[var(--wf-navy)]">Docs</a>
                <span class="mx-2">/</span>
                <span class="text-[var(--wf-navy)] font-semibold">{{ $currentArticle->title }}</span>
            </nav>

            <div class="flex flex-col lg:flex-row gap-8">
                <aside class="w-full lg:w-1/4 shrink-0">
                    <div class="bg-white rounded-2xl border border-[var(--wf-line)] p-4 sticky top-24">
                        <a href="{{ route('docs.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[var(--wf-muted)] hover:text-[var(--wf-navy)] mb-5">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                            Semua dokumentasi
                        </a>

                        <h3 class="text-sm font-bold uppercase tracking-wider text-[var(--wf-navy)] mb-3 px-2">Daftar isi</h3>

                        <nav class="space-y-3">
                            @foreach ($categories as $category)
                                @if ($category->documentations->count() > 0)
                                    <div x-data="{ expanded: {{ $category->id == $currentArticle->documentation_category_id ? 'true' : 'false' }} }">
                                        <button
                                            type="button"
                                            @click="expanded = !expanded"
                                            class="flex items-center justify-between w-full text-left font-semibold text-[var(--wf-navy)] px-2 py-1.5 rounded-lg hover:bg-[var(--wf-cream)]"
                                        >
                                            <span class="text-sm">{{ $category->name }}</span>
                                            <i class="fa-solid fa-chevron-down text-[0.65rem] text-[var(--wf-muted)] transition-transform" :class="expanded && 'rotate-180'"></i>
                                        </button>

                                        <ul x-show="expanded" x-cloak class="mt-1 ml-2 border-l border-[var(--wf-line)] pl-3 space-y-1">
                                            @foreach ($category->documentations as $doc)
                                                <li>
                                                    <a
                                                        href="{{ route('docs.show', $doc->slug) }}"
                                                        class="block py-1.5 text-sm transition-colors {{ $currentArticle->id === $doc->id ? 'text-[var(--wf-gold)] font-bold' : 'text-[var(--wf-muted)] hover:text-[var(--wf-navy)]' }}"
                                                    >
                                                        {{ $doc->title }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endforeach
                        </nav>
                    </div>
                </aside>

                <main class="w-full lg:w-3/4">
                    <article class="bg-white rounded-2xl border border-[var(--wf-line)] p-6 lg:p-10">
                        <header class="mb-8 border-b border-[var(--wf-line)] pb-6">
                            <p class="text-xs font-bold tracking-[0.16em] uppercase text-[var(--wf-gold)] mb-2">
                                {{ $currentArticle->category->name }}
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-bold text-[var(--wf-navy)] leading-tight">
                                {{ $currentArticle->title }}
                            </h1>
                            @if ($currentArticle->keywords)
                                <div class="flex flex-wrap gap-2 mt-4">
                                    @foreach (explode(',', $currentArticle->keywords) as $keyword)
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-[var(--wf-cream)] text-[var(--wf-muted)]">
                                            {{ trim($keyword) }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </header>

                        <div class="documentation-content">
                            {!! \App\Support\SafeHtml::fromRichText($currentArticle->content) !!}
                        </div>

                        @if ($currentArticle->related_resource)
                            <div class="mt-10 p-4 rounded-xl border border-[rgba(201,162,39,0.25)] bg-[rgba(201,162,39,0.08)]">
                                <h3 class="text-base font-bold text-[var(--wf-navy)] mb-1">Resource terkait</h3>
                                <p class="text-sm text-[var(--wf-muted)]">
                                    Artikel ini berkaitan dengan fitur <strong class="text-[var(--wf-navy)]">{{ $currentArticle->related_resource }}</strong>.
                                </p>
                            </div>
                        @endif
                    </article>
                </main>
            </div>
        </div>

        @include('front.partials.wf-footer')
    </div>
@endsection
