<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $handover->handover_number }}</title>
    <style>
        body{font-family:Arial,sans-serif;color:#0f172a;margin:28px}
        h1{font-size:24px;margin:0}
        .muted{color:#64748b}
        table{width:100%;border-collapse:collapse;margin-top:18px;font-size:12px}
        th,td{border-bottom:1px solid #e2e8f0;padding:8px;text-align:left}
        th{font-size:10px;text-transform:uppercase;color:#64748b;letter-spacing:.08em}
        .grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:18px}
        .box{border:1px solid #cbd5e1;border-radius:10px;padding:12px}
        @media print{button{display:none}}
    </style>
</head>
<body>
    <button onclick="window.print()">Print</button>
    <h1>Parcelman Rider Team Handover</h1>
    <p class="muted">{{ $handover->handover_number }}</p>
    <div class="grid">
        <div class="box"><strong>Team</strong><br>{{ $handover->team?->name ?? '-' }}</div>
        <div class="box"><strong>Leader</strong><br>{{ $handover->leader?->name ?? '-' }}<br>{{ $handover->leader?->phone ?? '' }}</div>
        <div class="box"><strong>Warehouse</strong><br>{{ $handover->warehouse?->name ?? '-' }}</div>
    </div>
    <div class="grid">
        <div class="box"><strong>Assigned</strong><br>{{ $handover->assigned_count }}</div>
        <div class="box"><strong>Received</strong><br>{{ $handover->received_count }}</div>
        <div class="box"><strong>Distributed</strong><br>{{ $handover->distributed_count }}</div>
    </div>
    <table>
        <thead><tr><th>#</th><th>Label</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($handover->items as $item)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $item->label?->barcode_value }}</td><td>{{ str_replace('_', ' ', $item->status) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <div class="grid" style="margin-top:44px">
        <div><strong>Prepared By</strong><br><br>Name<br><br>Signature<br><br>Date</div>
        <div><strong>Leader Received</strong><br><br>Name<br><br>Signature<br><br>Date</div>
        <div><strong>Warehouse Witness</strong><br><br>Name<br><br>Signature<br><br>Date</div>
    </div>
</body>
</html>
