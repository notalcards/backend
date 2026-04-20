<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Админ') — NatalCharts</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Inter, sans-serif; background: #0F0A1E; color: #E2E0F0; min-height: 100vh; display: flex; }

        .sidebar { width: 220px; min-height: 100vh; background: #1A1033; border-right: 1px solid rgba(124,58,237,.2); display: flex; flex-direction: column; padding: 24px 0; flex-shrink: 0; }
        .sidebar-logo { padding: 0 20px 24px; font-size: 18px; font-weight: 800; background: linear-gradient(135deg,#C4B5FD,#7C3AED); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: #A0A0C0; text-decoration: none; font-size: 14px; transition: all .2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(124,58,237,.2); color: #C4B5FD; }
        .sidebar-bottom { margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(124,58,237,.2); }

        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar { background: #1A1033; border-bottom: 1px solid rgba(124,58,237,.2); padding: 12px 28px; display: flex; justify-content: flex-end; align-items: center; gap: 16px; font-size: 14px; color: #A0A0C0; }
        .content { padding: 28px; flex: 1; }

        h1 { font-size: 24px; font-weight: 700; margin-bottom: 24px; }
        h2 { font-size: 18px; font-weight: 600; margin-bottom: 16px; }

        .card { background: #1A1033; border: 1px solid rgba(124,58,237,.2); border-radius: 12px; padding: 20px; }
        .grid { display: grid; gap: 16px; }
        .grid-4 { grid-template-columns: repeat(4,1fr); }
        .grid-2 { grid-template-columns: repeat(2,1fr); }
        .stat-label { font-size: 12px; color: #A0A0C0; margin-bottom: 4px; }
        .stat-value { font-size: 28px; font-weight: 700; color: #C4B5FD; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 10px 12px; font-size: 12px; color: #A0A0C0; border-bottom: 1px solid rgba(124,58,237,.2); font-weight: 500; text-transform: uppercase; letter-spacing: .05em; }
        td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,.05); vertical-align: middle; }
        tr:hover td { background: rgba(124,58,237,.05); }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-purple { background: rgba(124,58,237,.2); color: #C4B5FD; }
        .badge-red { background: rgba(239,68,68,.15); color: #FCA5A5; }
        .badge-green { background: rgba(34,197,94,.15); color: #86EFAC; }

        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; text-decoration: none; transition: all .2s; }
        .btn-primary { background: linear-gradient(135deg,#7C3AED,#9F67FF); color: #fff; }
        .btn-primary:hover { opacity: .9; }
        .btn-outline { background: transparent; border: 1px solid rgba(124,58,237,.5); color: #C4B5FD; }
        .btn-outline:hover { background: rgba(124,58,237,.15); }
        .btn-danger { background: rgba(239,68,68,.15); color: #FCA5A5; border: 1px solid rgba(239,68,68,.3); }
        .btn-danger:hover { background: rgba(239,68,68,.25); }
        .btn-sm { padding: 4px 10px; font-size: 12px; }

        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; color: #A0A0C0; margin-bottom: 6px; }
        input, select, textarea { width: 100%; background: #0F0A1E; border: 1px solid rgba(124,58,237,.3); color: #E2E0F0; padding: 8px 12px; border-radius: 8px; font-size: 14px; outline: none; }
        input:focus, select:focus { border-color: #7C3AED; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert-success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #86EFAC; }

        .pagination { display: flex; gap: 6px; margin-top: 20px; }
        .pagination a, .pagination span { padding: 6px 12px; border-radius: 6px; font-size: 13px; border: 1px solid rgba(124,58,237,.3); color: #C4B5FD; text-decoration: none; }
        .pagination .active span { background: #7C3AED; border-color: #7C3AED; color: #fff; }
        .pagination a:hover { background: rgba(124,58,237,.2); }

        @media(max-width:900px) { .grid-4 { grid-template-columns: repeat(2,1fr); } }
    </style>
</head>
<body>
<nav class="sidebar">
    <div class="sidebar-logo">🔮 NatalCharts</div>
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">📊 Дашборд</a>
    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">👥 Пользователи</a>
    <div class="sidebar-bottom">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" style="background:none;border:none;width:100%;cursor:pointer;">
                <a href="#" style="color:#A0A0C0;">🚪 Выйти</a>
            </button>
        </form>
    </div>
</nav>
<div class="main">
    <div class="topbar">
        <span>👤 {{ auth()->user()->name }}</span>
    </div>
    <div class="content">
        @if(session('success'))
            <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif
        @yield('content')
    </div>
</div>
</body>
</html>
