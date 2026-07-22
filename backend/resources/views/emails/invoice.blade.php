<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #222;">
    <p>Hi {{ $sale->customer->name }},</p>

    <p>Thanks for your purchase at {{ $sale->branch->name }}. Your invoice for Sale
        #{{ $sale->id }} is attached to this email.</p>

    <p>{{ config('app.name') }}</p>
</body>
</html>
