<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #1e293b;
        }
        .meta {
            color: #64748b;
            font-size: 10px;
            margin-bottom: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: #475569;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .empty {
            margin-top: 12px;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Generated on {{ $generatedAt }}</div>

    @if(!empty($rows) && !empty($headers))
        <table>
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($headers as $header)
                            <td>{{ $row[$header] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">No records found.</p>
    @endif
</body>
</html>

