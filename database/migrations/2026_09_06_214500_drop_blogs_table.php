<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('blogs');

        if (Schema::hasTable('documentations')) {
            DB::table('documentations')->where('slug', 'manajemen-blog-artikel')->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->where(function ($query) {
                    $query->where('name', 'like', '%:Blog')
                        ->orWhere('name', 'like', '%BlogResource%')
                        ->orWhere('name', 'like', 'widget_Blog%');
                })
                ->delete();
        }
    }

    public function down(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->string('category')->default('General');
            $table->json('tags')->nullable();
            $table->string('author_name')->default('Admin WOFINS');
            $table->string('author_title')->default('Financial Expert');
            $table->string('author_image')->nullable();
            $table->integer('read_time')->default(5);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'published_at']);
            $table->index(['category']);
            $table->index(['is_featured']);
            $table->index(['slug']);
        });
    }
};
