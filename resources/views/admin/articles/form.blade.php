@extends('admin.layout')
@section('title', $article->id ? 'Редактировать статью' : 'Новая статья')
@section('content')
<div style="display:flex;align-items:center;gap:16px;margin-bottom:24px">
    <a href="{{ route('admin.articles.index') }}" class="btn btn-outline btn-sm">← Назад</a>
    <h1 style="margin:0">{{ $article->id ? 'Редактировать статью' : 'Новая статья' }}</h1>
</div>

@if($errors->any())
    <div class="alert" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#FCA5A5;margin-bottom:16px">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ $article->id ? route('admin.articles.update', $article) : route('admin.articles.store') }}">
    @csrf
    @if($article->id) @method('PUT') @endif

    <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">

        {{-- Left column --}}
        <div>
            <div class="card" style="margin-bottom:20px">
                <div class="form-group">
                    <label>Заголовок *</label>
                    <input type="text" name="title" value="{{ old('title', $article->title) }}" required id="titleInput" oninput="autoSlug(this.value)">
                </div>
                <div class="form-group">
                    <label>Slug (URL)</label>
                    <input type="text" name="slug" id="slugInput" value="{{ old('slug', $article->slug) }}" placeholder="auto-generate">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label>Краткое описание (excerpt) *</label>
                    <textarea name="excerpt" rows="3" required>{{ old('excerpt', $article->excerpt) }}</textarea>
                </div>
            </div>

            {{-- EditorJS --}}
            <div class="card">
                <label style="margin-bottom:12px;display:block">Содержимое статьи</label>
                <div id="editorjs" style="background:#0F0A1E;border:1px solid rgba(124,58,237,.3);border-radius:8px;min-height:300px;padding:8px 0"></div>
                <input type="hidden" name="content_json" id="contentJson">
            </div>
        </div>

        {{-- Right column --}}
        <div>
            <div class="card" style="margin-bottom:16px">
                <h2 style="margin-bottom:16px">Публикация</h2>
                <div class="form-group">
                    <label>Дата публикации</label>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', $article->published_at ? $article->published_at->format('Y-m-d\TH:i') : '') }}">
                    <div style="font-size:12px;color:#A0A0C0;margin-top:4px">Оставьте пустым — статья останется черновиком</div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Сохранить</button>
            </div>

            <div class="card" style="margin-bottom:16px">
                <h2 style="margin-bottom:16px">Оформление</h2>
                <div class="form-group">
                    <label>Эмодзи</label>
                    <input type="text" name="emoji" value="{{ old('emoji', $article->emoji ?? '📝') }}" maxlength="10">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label>Градиент обложки</label>
                    <input type="text" name="gradient" value="{{ old('gradient', $article->gradient ?? 'linear-gradient(135deg, #2D1B69 0%, #7C3AED 100%)') }}">
                </div>
            </div>

            <div class="card">
                <h2 style="margin-bottom:16px">Метаданные</h2>
                <div class="form-group">
                    <label>Категория</label>
                    <input type="text" name="category" value="{{ old('category', $article->category ?? 'Астрология') }}">
                </div>
                <div class="form-group">
                    <label>Автор</label>
                    <input type="text" name="author" value="{{ old('author', $article->author ?? 'Редакция') }}">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label>Время чтения</label>
                    <input type="text" name="read_time" value="{{ old('read_time', $article->read_time ?? '5 мин') }}">
                </div>
            </div>
        </div>

    </div>
</form>

{{-- EditorJS assets --}}
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/delimiter@latest"></script>

<script>
// Auto-generate slug from title
function transliterate(str) {
    const map = {'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'yo','ж':'zh','з':'z','и':'i','й':'j','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'ts','ч':'ch','ш':'sh','щ':'sch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya'};
    return str.toLowerCase().split('').map(c => map[c] ?? c).join('').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
}
function autoSlug(val) {
    const slugEl = document.getElementById('slugInput');
    if (!slugEl._touched) slugEl.value = transliterate(val);
}
document.getElementById('slugInput').addEventListener('input', function() { this._touched = true; });

// EditorJS init
const existingData = @json($article->content ?? null);

const editor = new EditorJS({
    holder: 'editorjs',
    data: existingData && existingData.blocks ? existingData : { blocks: [] },
    tools: {
        header: { class: Header, inlineToolbar: true },
        list: { class: List, inlineToolbar: true },
        quote: { class: Quote, inlineToolbar: true },
        delimiter: Delimiter,
    },
    placeholder: 'Начните писать статью...',
    i18n: {
        messages: {
            ui: { blockTunes: { toggler: { 'Click to tune': 'Настроить', 'or drag to move': 'или перетащить' } }, inlineToolbar: { converter: { 'Convert to': 'Конвертировать' } }, toolbar: { toolbox: { Add: 'Добавить' } } },
            toolNames: { Text: 'Параграф', Heading: 'Заголовок', List: 'Список', Quote: 'Цитата', Delimiter: 'Разделитель' },
            tools: { list: { Ordered: 'Нумерованный', Unordered: 'Маркированный' } },
        }
    },
});

// Save EditorJS content before form submit
document.querySelector('form').addEventListener('submit', async function(e) {
    e.preventDefault();
    try {
        const data = await editor.save();
        document.getElementById('contentJson').value = JSON.stringify(data);
    } catch (err) {
        document.getElementById('contentJson').value = '';
    }
    this.submit();
});
</script>

<style>
.codex-editor { color: #E2E0F0 !important; }
.codex-editor__redactor { padding: 12px 16px !important; }
.ce-block__content { max-width: none !important; }
.ce-toolbar__plus, .ce-toolbar__settings-btn { color: #C4B5FD !important; }
.ce-toolbar__plus:hover, .ce-toolbar__settings-btn:hover { background: rgba(124,58,237,.3) !important; }
.ce-inline-toolbar, .ce-conversion-toolbar, .ce-settings { background: #1A1033 !important; border: 1px solid rgba(124,58,237,.3) !important; border-radius: 8px !important; }
.ce-inline-tool, .ce-conversion-tool { color: #C4B5FD !important; }
.ce-inline-tool:hover, .ce-conversion-tool:hover { background: rgba(124,58,237,.3) !important; }
.cdx-block { color: #E2E0F0 !important; }
.cdx-quote__text, .cdx-quote__caption { color: #E2E0F0 !important; background: transparent !important; border-color: rgba(124,58,237,.4) !important; }
.ce-delimiter:before { color: #7C3AED !important; }
</style>
@endsection
