@extends('admin.layout')
@section('title', $user->name)
@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">← Назад</a>
    <h1 style="margin:0;">{{ $user->name }}</h1>
    @if($user->is_blocked)
        <span class="badge badge-red">Заблокирован</span>
    @endif
</div>

<div class="grid grid-2" style="margin-bottom:24px;">
    <div class="card">
        <h2>👤 Профиль</h2>
        <table>
            <tr><td style="color:#A0A0C0;">Email</td><td>{{ $user->email }}</td></tr>
            <tr><td style="color:#A0A0C0;">Кредиты</td><td><strong style="color:#C4B5FD;">💎 {{ number_format($user->credits) }}</strong></td></tr>
            <tr><td style="color:#A0A0C0;">Профили</td><td>{{ $user->profiles->count() }}</td></tr>
            <tr><td style="color:#A0A0C0;">Регистрация</td><td>{{ $user->created_at->format('d.m.Y H:i') }}</td></tr>
            <tr><td style="color:#A0A0C0;">Последнее обновление</td><td>{{ $user->updated_at->format('d.m.Y H:i') }}</td></tr>
        </table>

        <div style="display:flex;gap:8px;margin-top:20px;">
            @if($user->is_blocked)
                <form method="POST" action="{{ route('admin.users.unblock', $user->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline">✅ Разблокировать</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.block', $user->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Заблокировать пользователя?')">🚫 Заблокировать</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <h2>💎 Начислить кредиты</h2>
        <form method="POST" action="{{ route('admin.users.credits', $user->id) }}">
            @csrf
            <div class="form-group">
                <label>Количество кредитов</label>
                <input type="number" name="amount" min="1" value="100" required>
            </div>
            <div class="form-group">
                <label>Причина (необязательно)</label>
                <input type="text" name="description" placeholder="Начисление кредитов администратором">
            </div>
            <button type="submit" class="btn btn-primary">Начислить</button>
        </form>
    </div>
</div>

@if($user->profiles->count())
<div class="card" style="margin-bottom:24px;">
    <h2>🌟 Профили рождения</h2>
    <table>
        <thead><tr><th>Имя</th><th>Дата рождения</th><th>Место</th><th>По умолчанию</th></tr></thead>
        <tbody>
        @foreach($user->profiles as $profile)
            <tr>
                <td>{{ $profile->name }}</td>
                <td>{{ $profile->birth_date }} {{ $profile->birth_time }}</td>
                <td>{{ $profile->birth_place }}</td>
                <td>{{ $profile->is_default ? '✅' : '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if($user->charts->count())
<div class="card" style="margin-bottom:24px;">
    <h2>📊 Последние карты</h2>
    <table>
        <thead><tr><th>Тип</th><th>Дата</th><th>Кредиты</th></tr></thead>
        <tbody>
        @foreach($user->charts as $chart)
            <tr>
                <td><span class="badge badge-purple">{{ $chart->type }}</span></td>
                <td style="font-size:12px;color:#A0A0C0;">{{ $chart->created_at->format('d.m.Y H:i') }}</td>
                <td>{{ $chart->credits_spent }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if($user->creditTransactions->count())
<div class="card">
    <h2>💳 История кредитов</h2>
    <table>
        <thead><tr><th>Сумма</th><th>Тип</th><th>Описание</th><th>Дата</th></tr></thead>
        <tbody>
        @foreach($user->creditTransactions as $tx)
            <tr>
                <td>
                    <span class="badge {{ $tx->type === 'credit' ? 'badge-green' : 'badge-red' }}">
                        {{ $tx->type === 'credit' ? '+' : '-' }}{{ $tx->amount }}
                    </span>
                </td>
                <td>{{ $tx->type === 'credit' ? 'Начисление' : 'Списание' }}</td>
                <td style="font-size:13px;color:#A0A0C0;">{{ $tx->description }}</td>
                <td style="font-size:12px;color:#A0A0C0;">{{ $tx->created_at->format('d.m.Y H:i') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
