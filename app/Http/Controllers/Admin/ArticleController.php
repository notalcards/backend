<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::orderByDesc('created_at')->paginate(20);
        return view('admin.articles.index', compact('articles'));
    }

    public function create()
    {
        return view('admin.articles.form', ['article' => new Article()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['content'] = $request->input('content_json') ? json_decode($request->input('content_json'), true) : null;
        Article::create($data);
        return redirect()->route('admin.articles.index')->with('success', 'Статья создана');
    }

    public function edit(Article $article)
    {
        return view('admin.articles.form', compact('article'));
    }

    public function update(Request $request, Article $article)
    {
        $data = $this->validated($request, $article->id);
        $data['content'] = $request->input('content_json') ? json_decode($request->input('content_json'), true) : null;
        $article->update($data);
        return redirect()->route('admin.articles.index')->with('success', 'Статья обновлена');
    }

    public function destroy(Article $article)
    {
        $article->delete();
        return redirect()->route('admin.articles.index')->with('success', 'Статья удалена');
    }

    public function publish(Article $article)
    {
        $article->update(['published_at' => $article->published_at ? null : now()]);
        return back()->with('success', $article->published_at ? 'Опубликована' : 'Снята с публикации');
    }

    public function uploadImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['image' => 'required|image|max:10240']);

        $file = $request->file('image');
        $mime = $file->getMimeType();

        $src = match (true) {
            in_array($mime, ['image/jpeg', 'image/jpg']) => imagecreatefromjpeg($file->path()),
            $mime === 'image/png'  => imagecreatefrompng($file->path()),
            $mime === 'image/webp' => imagecreatefromwebp($file->path()),
            $mime === 'image/gif'  => imagecreatefromgif($file->path()),
            default => null,
        };

        if (!$src) {
            return response()->json(['success' => 0, 'message' => 'Unsupported format']);
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        $maxW  = 1000;

        if ($origW > $maxW) {
            $newW = $maxW;
            $newH = (int) round($origH * $maxW / $origW);
            $dst  = imagecreatetruecolor($newW, $newH);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
        }

        $dir = public_path('uploads/articles');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = uniqid('img_', true) . '.webp';
        imagewebp($src, $dir . '/' . $filename, 85);
        imagedestroy($src);

        return response()->json([
            'success' => 1,
            'file'    => ['url' => url('uploads/articles/' . $filename)],
        ]);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'excerpt'    => 'required|string',
            'emoji'      => 'nullable|string|max:10',
            'gradient'   => 'nullable|string|max:255',
            'category'   => 'nullable|string|max:100',
            'author'     => 'nullable|string|max:100',
            'read_time'  => 'nullable|string|max:20',
            'slug'       => 'nullable|string|max:255',
        ]);

        $slug = $request->input('slug') ?: Str::slug($request->input('title'));
        $unique = Article::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists();
        if ($unique) {
            $slug = $slug . '-' . time();
        }

        return [
            'title'      => $request->input('title'),
            'excerpt'    => $request->input('excerpt'),
            'emoji'      => $request->input('emoji', '📝'),
            'gradient'   => $request->input('gradient', 'linear-gradient(135deg, #2D1B69 0%, #7C3AED 100%)'),
            'category'   => $request->input('category', 'Астрология'),
            'author'     => $request->input('author', 'Редакция'),
            'read_time'  => $request->input('read_time', '5 мин'),
            'slug'       => $slug,
            'published_at' => $request->input('published_at') ?: null,
        ];
    }
}
