@extends('admin.layout')
@section('title', 'Статьи')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <h1 style="margin:0">Статьи</h1>
    <a href="{{ route('admin.articles.create') }}" class="btn btn-primary">+ Новая статья</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Заголовок</th>
                <th>Категория</th>
                <th>Автор</th>
                <th>Статус</th>
                <th>Дата</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($articles as $article)
            <tr>
                <td>
                    <span style="font-size:20px;margin-right:8px">{{ $article->emoji }}</span>
                    <strong>{{ $article->title }}</strong>
                    <div style="font-size:12px;color:#A0A0C0;margin-top:2px">/blog/{{ $article->slug }}</div>
                </td>
                <td><span class="badge badge-purple">{{ $article->category }}</span></td>
                <td style="color:#A0A0C0">{{ $article->author }}</td>
                <td>
                    @if($article->published_at && $article->published_at->isPast())
                        <span class="badge badge-green">Опубликовано</span>
                    @else
                        <span class="badge" style="background:rgba(255,255,255,.07);color:#A0A0C0">Черновик</span>
                    @endif
                </td>
                <td style="color:#A0A0C0;font-size:13px">{{ $article->created_at->format('d.m.Y') }}</td>
                <td style="white-space:nowrap;text-align:right">
                    <form method="POST" action="{{ route('admin.articles.publish', $article) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm">
                            {{ $article->published_at && $article->published_at->isPast() ? 'Снять' : 'Опубликовать' }}
                        </button>
                    </form>
                    <a href="{{ route('admin.articles.edit', $article) }}" class="btn btn-outline btn-sm">Редактировать</a>
                    <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" style="display:inline" onsubmit="return confirm('Удалить статью?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Удалить</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#A0A0C0;padding:32px">Статей пока нет</td></tr>
        @endforelse
        </tbody>
    </table>

    @if($articles->hasPages())
        <div class="pagination" style="margin-top:20px">
            {{ $articles->links() }}
        </div>
    @endif
</div>
@endsection
