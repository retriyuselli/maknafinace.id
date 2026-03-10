<x-filament-panels::page>
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigasi -->
        <div class="w-full lg:w-1/4 flex-shrink-0">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4 sticky top-24">
                <div class="mb-4">
                    <input 
                        wire:model.live.debounce.500ms="search" 
                        type="search" 
                        placeholder="Cari dokumentasi..." 
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"
                    >
                </div>

                <nav class="space-y-4">
                    @foreach ($this->categories as $category)
                        @if($category->documentations->count() > 0)
                            <div x-data="{ expanded: true }">
                                <button 
                                    @click="expanded = !expanded" 
                                    class="flex items-center justify-between w-full text-left font-medium text-gray-900 dark:text-gray-100 hover:text-primary-600 mb-2"
                                >
                                    <span class="flex items-center gap-2">
                                        @if($category->icon)
                                            <x-filament::icon
                                                :icon="$category->icon"
                                                class="h-4 w-4 text-gray-500"
                                            />
                                        @endif
                                        {{ $category->name }}
                                    </span>
                                    <x-filament::icon
                                        icon="heroicon-m-chevron-down"
                                        class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                        x-bind:class="expanded ? 'rotate-0' : '-rotate-90'"
                                    />
                                </button>

                                <ul x-show="expanded" x-collapse class="space-y-1 ml-2 border-l border-gray-200 dark:border-gray-700 pl-3">
                                    @foreach ($category->documentations as $doc)
                                        <li>
                                            <a 
                                                href="{{ App\Filament\Pages\HelpCenter::getUrl(['article' => $doc->slug]) }}"
                                                class="block py-1 text-sm transition-colors duration-200 {{ $currentArticle?->id === $doc->id ? 'text-primary-600 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}"
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
        </div>

        <!-- Konten Utama -->
        <div class="w-full lg:w-3/4">
            @if ($currentArticle)
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 lg:p-10">
                    <header class="mb-8 border-b border-gray-200 dark:border-gray-800 pb-6">
                        <div class="flex items-center gap-2 text-sm text-primary-600 font-medium mb-2">
                            <span class="bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 px-2 py-1 rounded-md">
                                {{ $currentArticle->category->name }}
                            </span>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-1 tracking-tight">
                            {{ $currentArticle->title }}
                        </h1>
                        @if($currentArticle->keywords)
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach(explode(',', $currentArticle->keywords) as $keyword)
                                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                        {{ trim($keyword) }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </header>

                    <style>
                        .docs-content h2 {
                            font-size: 16px;
                            font-weight: 700;
                            margin-top: 2rem;
                            margin-bottom: 1rem;
                            color: var(--tw-prose-headings);
                            line-height: 1.3;
                        }
                        .docs-content h3 {
                            font-size: 16px;
                            font-weight: 600;
                            margin-top: 1.5rem;
                            margin-bottom: 0.75rem;
                            color: var(--tw-prose-headings);
                            line-height: 1.2;
                        }
                        .docs-content p {
                            font-size: 14px;
                            margin-bottom: 1rem;
                            line-height: 1.6;
                        }
                        .docs-content ul {
                            font-size: 14px;
                            list-style-type: disc;
                            padding-left: 1.5rem;
                            margin-bottom: 1rem;
                        }
                        .docs-content ol {
                            font-size: 14px;
                            list-style-type: decimal;
                            padding-left: 1.5rem;
                            margin-bottom: 1rem;
                        }
                        .docs-content li {
                            margin-bottom: 0.5rem;
                            padding-left: 0.25rem;
                        }
                        .docs-content li::marker {
                            color: #d1d5db; /* gray-300 */
                        }
                        .dark .docs-content li::marker {
                            color: #4b5563; /* gray-600 */
                        }
                        .docs-content pre {
                            background-color: #f3f4f6;
                            padding: 1rem;
                            border-radius: 0.5rem;
                            overflow-x: auto;
                            margin-bottom: 1rem;
                            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                            font-size: 14px;
                        }
                        .dark .docs-content pre {
                            background-color: #1f2937;
                        }
                        .docs-content blockquote {
                            font-size: 14px;
                            border-left: 4px solid #3b82f6; /* primary-500 */
                            padding-left: 1rem;
                            margin-left: 0;
                            margin-bottom: 1rem;
                            font-style: italic;
                            color: #4b5563;
                        }
                        .dark .docs-content blockquote {
                            color: #9ca3af;
                        }
                        /* Custom Callouts */
                        .docs-content .bg-blue-50 {
                            background-color: #eff6ff;
                            border: 1px solid #dbeafe;
                            border-radius: 0.5rem;
                            padding: 1rem;
                            margin-bottom: 1rem;
                        }
                        .dark .docs-content .bg-blue-50 {
                            background-color: rgba(30, 64, 175, 0.2);
                            border-color: rgba(30, 64, 175, 0.4);
                        }
                        .docs-content .bg-yellow-50 {
                            background-color: #fefce8;
                            border: 1px solid #fef9c3;
                            border-radius: 0.5rem;
                            padding: 1rem;
                            margin-bottom: 1rem;
                        }
                        .dark .docs-content .bg-yellow-50 {
                            background-color: rgba(234, 179, 8, 0.1);
                            border-color: rgba(234, 179, 8, 0.2);
                        }
                    </style>

                    <div class="docs-content prose prose-sm dark:prose-invert max-w-none text-black dark:text-gray-300">
                        {!! $currentArticle->content !!}
                    </div>

                    @if($currentArticle->related_resource)
                        <div class="mt-12 pt-6 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex items-start gap-4 p-4 rounded-lg bg-primary-50 dark:bg-primary-900/10 border border-primary-100 dark:border-primary-900/20">
                                <div class="p-2 bg-white dark:bg-gray-800 rounded-md shadow-sm text-primary-600 dark:text-primary-400">
                                    <x-filament::icon icon="heroicon-o-link" class="h-6 w-6" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Fitur Terkait</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Artikel ini berkaitan langsung dengan fitur 
                                        <span class="font-mono font-medium text-primary-600 dark:text-primary-400 bg-white dark:bg-gray-800 px-1.5 py-0.5 rounded border border-primary-200 dark:border-primary-800 text-xs">
                                            {{ $currentArticle->related_resource }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="space-y-10">
                    <!-- Hero Section -->
                    <div class="text-center py-10 px-4">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                            Dokumentasi Sistem
                        </h2>
                        <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-600 dark:text-gray-400">
                            Panduan lengkap penggunaan sistem Makna Finance. Pelajari cara mengelola aset, keuangan, dan pengaturan sistem dengan mudah.
                        </p>
                    </div>

                    <!-- Quick Links Grid -->
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Kategori Bantuan</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($this->categories as $category)
                                @if($category->documentations->count() > 0)
                                    <div class="group relative rounded-2xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 transition hover:shadow-md hover:ring-primary-500/50 dark:hover:ring-primary-400/50">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-100 dark:ring-primary-800 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40 transition">
                                            @if($category->icon)
                                                <x-filament::icon
                                                    :icon="$category->icon"
                                                    class="h-6 w-6 text-primary-600 dark:text-primary-400"
                                                />
                                            @else
                                                <x-filament::icon
                                                    icon="heroicon-o-document-text"
                                                    class="h-6 w-6 text-primary-600 dark:text-primary-400"
                                                />
                                            @endif
                                        </div>
                                        
                                        <h4 class="mt-4 text-sm font-semibold leading-7 text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition">
                                            <a href="{{ App\Filament\Pages\HelpCenter::getUrl(['article' => $category->documentations->first()->slug]) }}">
                                                <span class="absolute inset-0"></span>
                                                {{ $category->name }}
                                            </a>
                                        </h4>
                                        
                                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">
                                            {{ $category->documentations->count() }} artikel tersedia.
                                            @if($category->documentations->first())
                                                Mulai dari: "{{ Str::limit($category->documentations->first()->title, 30) }}"
                                            @endif
                                        </p>

                                        <div class="mt-4 flex items-center text-sm font-medium text-primary-600 dark:text-primary-400">
                                            Lihat Panduan
                                            <x-filament::icon icon="heroicon-m-arrow-right" class="ml-1 h-4 w-4" />
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
