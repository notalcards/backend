@extends('admin.layout')
@section('title', 'Дашборд')
@section('content')

<h1>📊 Дашборд</h1>

<div class="grid grid-4" style="margin-bottom:24px;">
    <div class="card">
        <div class="stat-label">Всего пользователей</div>
        <div class="stat-value">{{ $stats['users']['total'] }}</div>
    </div>
    <div class="card">
        <div class="stat-label">Новых сегодня</div>
        <div class="stat-value">{{ $stats['users']['new_today'] }}</div>
    </div>
    <div class="card">
        <div class="stat-label">Новых за неделю</div>
        <div class="stat-value">{{ $stats['users']['new_week'] }}</div>
    </div>
    <div class="card">
        <div class="stat-label">Активных за 7 дней</div>
        <div class="stat-value">{{ $stats['users']['active_last_7_days'] }}</div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2>📈 Карты и прогнозы</h2>
        <div style="font-size:32px;font-weight:700;color:#C4B5FD;margin-bottom:16px;">{{ $stats['charts']['total'] }}</div>
        @if($stats['charts']['by_type']->isEmpty())
            <p style="color:#A0A0C0;font-size:14px;">Пока нет построенных карт</p>
        @else
            <table>
                <thead><tr><th>Тип</th><th>Количество</th></tr></thead>
                <tbody>
                @foreach($stats['charts']['by_type'] as $type => $count)
                    <tr>
                        <td>{{ $type }}</td>
                        <td><span class="badge badge-purple">{{ $count }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="card">
        <h2>👥 Пользователи</h2>
        <table>
            <tbody>
            <tr><td style="color:#A0A0C0;">За месяц</td><td><span class="badge badge-purple">{{ $stats['users']['new_month'] }}</span></td></tr>
            <tr><td style="color:#A0A0C0;">За неделю</td><td><span class="badge badge-purple">{{ $stats['users']['new_week'] }}</span></td></tr>
            <tr><td style="color:#A0A0C0;">Сегодня</td><td><span class="badge badge-purple">{{ $stats['users']['new_today'] }}</span></td></tr>
            </tbody>
        </table>
        <div style="margin-top:16px;">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Все пользователи →</a>
        </div>
    </div>
</div>

@endsection
