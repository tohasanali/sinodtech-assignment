<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .meta { margin-bottom: 20px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Invoice #{{ $sale->id }}</h1>
    <div class="meta">
        <div>Date: {{ $sale->created_at->format('Y-m-d H:i') }}</div>
        <div>Branch: {{ $sale->branch->name }}</div>
        <div>Customer: {{ $sale->customer->name }} ({{ $sale->customer->email }})</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right">Total</td>
                <td class="text-right">
                    {{ number_format($sale->items->sum(fn ($item) => $item->unit_price * $item->quantity), 2) }}
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
