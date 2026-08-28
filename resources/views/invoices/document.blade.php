<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Инвойс {{ $invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1c1c1c; margin: 32px; }
        h1 { color: #4b71d6; font-size: 22px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .meta { color: #666; margin-bottom: 16px; }
        .total { font-size: 18px; font-weight: 700; margin-top: 16px; }
    </style>
</head>
<body>
<h1>Инвойс {{ $invoice->number }}</h1>
<p class="meta">Статус: {{ $invoice->status }} · {{ now()->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</p>
<table>
    <tr><th>VIN</th><td>{{ $lot->vin }}</td></tr>
    <tr><th>Лот</th><td>{{ $lot->lot_number ?? '—' }}</td></tr>
    <tr><th>Авто</th><td>{{ $lot->transport_name ?? '—' }}</td></tr>
    <tr><th>Клиент</th><td>{{ $lot->client?->full_name ?? $lot->buyer?->name ?? '—' }}</td></tr>
    <tr><th>Сумма</th><td>{{ $invoice->amount }} {{ $invoice->currency }}</td></tr>
</table>
<p class="total">К оплате: {{ $invoice->amount }} {{ $invoice->currency }}</p>
</body>
</html>
