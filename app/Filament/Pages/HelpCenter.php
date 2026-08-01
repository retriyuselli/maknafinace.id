<?php

namespace App\Filament\Pages;

use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Request;
use UnitEnum;
use BackedEnum;

class HelpCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected string $view = 'filament.pages.help-center';

    protected static ?string $title = 'Pusat Bantuan';

    protected static string|UnitEnum|null $navigationGroup = 'Knowledge Base';

    protected static ?int $navigationSort = 3;

    public ?Documentation $currentArticle = null;
    public $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'articleSlug' => ['except' => '', 'as' => 'article'],
    ];

    public $articleSlug = null;

    public function mount(): void
    {
        $this->articleSlug = Request::query('article');
        
        if ($this->articleSlug) {
            $this->currentArticle = Documentation::where('slug', $this->articleSlug)
                ->where('is_published', true)
                ->first();
        } else {
            // Default ke artikel pertama atau halaman intro
            $this->currentArticle = Documentation::where('is_published', true)
                ->orderBy('order')
                ->first();
        }
    }

    public function getCategoriesProperty()
    {
        return DocumentationCategory::with(['documentations' => function ($query) {
            $query->where('is_published', true)->orderBy('order');
        }])
        ->where('is_active', true)
        ->orderBy('order')
        ->get();
    }

    public function updatedSearch()
    {
        // Logic pencarian bisa ditambahkan di sini atau langsung di view dengan AlpineJS
    }
}
