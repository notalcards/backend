<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в панель администратора</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f3f4f6; }
        .card { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { margin-top: 0; font-size: 1.5rem; }
        label { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; }
        input { width: 100%; box-sizing: border-box; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; margin-bottom: 1rem; font-size: 1rem; }
        button { width: 100%; padding: 0.625rem; background: #4f46e5; color: #fff; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
        button:hover { background: #4338ca; }
        .error { color: #dc2626; font-size: 0.875rem; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Панель администратора</h1>
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <p class="error">{{ $error }}</p>
            @endforeach
        @endif
        <form method="POST" action="{{ route('admin.login') }}">
            @csrf
            <label for="login">Логин</label>
            <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>
