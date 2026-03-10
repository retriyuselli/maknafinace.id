@extends('layouts.app')

@section('title', $currentArticle->title)

@section('content')
    <style>
        /* Custom Styles for Documentation Content */
        .documentation-content h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #1f2937;
        }
        .documentation-content h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        .documentation-content p {
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            line-height: 1.5;
            color: #4b5563;
        }
        .documentation-content ul {
            font-size: 0.9rem;
            list-style-type: disc;
            padding-left: 1.25rem;
            margin-bottom: 0.75rem;
        }
        .documentation-content ol {
            font-size: 0.9rem;
            list-style-type: decimal;
            padding-left: 1.25rem;
            margin-bottom: 0.75rem;
        }
        .documentation-content li {
            margin-bottom: 0.25rem;
            color: #4b5563;
        }
        .documentation-content pre {
            background-color: #f3f4f6;
            padding: 0.75rem;
            border-radius: 0.375rem;
            overflow-x: auto;
            margin-bottom: 0.75rem;
            font-family: monospace;
            font-size: 0.8rem;
        }
        .documentation-content blockquote {
            font-size: 0.9rem;
            border-left: 3px solid #3b82f6;
            padding-left: 0.75rem;
            margin-left: 0;
            margin-bottom: 0.75rem;
            font-style: italic;
            color: #4b5563;
        }
        .documentation-content hr {
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
            border-color: #e5e7eb;
        }
        .documentation-content strong {
            font-weight: 600;
            color: #111827;
        }
        /* Style untuk Callout/Alert boxes yang dibuat di RichEditor */
        .documentation-content .bg-blue-50 {
            font-size: 0.9rem;
            background-color: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .documentation-content .bg-yellow-50 {
            font-size: 0.9rem;
            background-color: #fefce8;
            border: 1px solid #fef9c3;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
        }
    </style>

    <div class="min-h-screen bg-gray-50">
        <!-- Navigation Header -->
        @include('front.header')

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Sidebar Navigasi -->
                <div class="w-full lg:w-1/4 flex-shrink-0">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sticky top-24">
                        <a href="{{ route('docs.index') }}" class="flex items-center text-sm text-gray-500 hover:text-blue-600 mb-6 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1 group-hover:-translate-x-1 transition-transform">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                            Kembali ke Menu Utama
                        </a>

                        <h3 class="text-lg font-semibold text-gray-900 mb-4 px-2">Daftar Isi</h3>
                        
                        <nav class="space-y-4">
                            @foreach ($categories as $category)
                                @if($category->id == $currentArticle->documentation_category_id && $category->documentations->count() > 0)
                                    <div x-data="{ expanded: true }">
                                        <button 
                                            @click="expanded = !expanded" 
                                            class="flex items-center justify-between w-full text-left font-medium text-gray-900 hover:text-blue-600 px-2 py-1 rounded-md hover:bg-gray-50 transition-colors"
                                        >
                                            <span class="flex items-center gap-2">
                                                {{ $category->name }}
                                            </span>
                                            <svg 
                                                class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                                :class="expanded ? 'rotate-0' : '-rotate-90'"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <ul x-show="expanded" x-collapse class="space-y-1 ml-2 border-l border-gray-200 pl-3 mt-1">
                                            @foreach ($category->documentations as $doc)
                                                <li>
                                                    <a 
                                                        href="{{ route('docs.show', $doc->slug) }}"
                                                        class="block py-1 text-sm transition-colors duration-200 {{ isset($currentArticle) && $currentArticle->id === $doc->id ? 'text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}"
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
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:p-10">
                        <header class="mb-8 border-b border-gray-200 pb-6">
                            <div class="flex items-center gap-2 text-sm text-blue-600 font-medium mb-2">
                                <span>{{ $currentArticle->category->name }}</span>
                            </div>
                            <h1 class="text-xl font-bold text-gray-900 mb-2">
                                {{ $currentArticle->title }}
                            </h1>
                            @if($currentArticle->keywords)
                                <div class="flex flex-wrap gap-2">
                                    @foreach(explode(',', $currentArticle->keywords) as $keyword)
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600">
                                            {{ trim($keyword) }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </header>

                        <!-- Menggunakan custom class documentation-content untuk styling -->
                        <div class="documentation-content text-sm">
                            {!! $currentArticle->content !!}
                        </div>

                        @if($currentArticle->related_resource)
                            <div class="mt-10 p-4 bg-blue-50 rounded-lg border border-blue-100">
                                <h3 class="text-base font-semibold text-blue-900 mb-2">Resource Terkait</h3>
                                <p class="text-blue-700 text-sm mb-2  ">
                                    Artikel ini berkaitan dengan fitur <strong>{{ $currentArticle->related_resource }}</strong>.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @include('front.footer')
    </div>
@endsection
