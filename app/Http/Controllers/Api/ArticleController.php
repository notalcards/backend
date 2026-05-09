<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = Article::published()
            ->orderByDesc('published_at')
            ->get(['id', 'slug', 'title', 'excerpt', 'emoji', 'gradient', 'category', 'author', 'read_time', 'published_at']);

        return response()->json($articles);
    }

    public function show(string $slug): JsonResponse
    {
        $article = Article::published()->where('slug', $slug)->firstOrFail();

        return response()->json($article);
    }
}
