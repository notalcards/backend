@extends('admin.layout')
@section('title', 'Пользователи')
@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <h1 style="margin:0;">👥 Пользователи</h1>
</div>

<div class="card" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;">
        <input name="search" placeholder="Поиск по имени или email" value="{{ request('search') }}" style="max-width:280px;">
        <select name="filter" style="max-width:160px;">
            <option value="">Все</option>
            <option value="active" {{ request('filter')=='active' ? 'selected' : '' }}>Активные</option>
            <option value="blocked" {{ request('filter')=='blocked' ? 'selected' : '' }}>Заблокированные</option>
        </select>
        <button type="submit" class="btn btn-primary">Найти</button>
        @if(request()->hasAny(['search','filter']))
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Сбросить</a>
        @endif
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>Кредиты</th>
                <th>Профили</th>
                <th>Карты</th>
                <th>Дата регистрации</th>
                <th>Статус</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($users as $user)
            <tr>
                <td style="color:#A0A0C0;">{{ $user->id }}</td>
                <td>
                    <div style="font-weight:500;">{{ $user->name }}</div>
                    <div style="font-size:12px;color:#A0A0C0;">{{ $user->email }}</div>
                </td>
                <td><span class="badge badge-purple">💎 {{ number_format($user->credits) }}</span></td>
                <td>{{ $user->profiles_count }}</td>
                <td>{{ $user->charts_count }}</td>
                <td style="font-size:12px;color:#A0A0C0;">{{ $user->created_at->format('d.m.Y H:i') }}</td>
                <td>
                    @if($user->is_blocked)
                        <span class="badge badge-red">Заблокирован</span>
                    @elseif($user->is_admin)
                        <span class="badge badge-purple">Админ</span>
                    @else
                        <span class="badge badge-green">Активен</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-outline btn-sm">Открыть</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center;color:#A0A0C0;padding:32px;">Пользователи не найдены</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="pagination">
        {{ $users->links() }}
    </div>
</div>

@endsection
