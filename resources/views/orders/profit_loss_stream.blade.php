<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laba Rugi — {{ $order->prospect->name_event ?? $order->id }}</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: system-ui, sans-serif;
            background: #f3f4f6;
        }

        .preview-frame {
            width: 100%;
            height: calc(100vh - 72px);
            border: 0;
            background: #fff;
        }

        .preview-footer {
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: #fff;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }

        .btn-download {
            background: #0b1f3a;
            color: #fff;
        }

        .btn-download:hover {
            background: #071526;
        }
    </style>
</head>
<body>
    <iframe class="preview-frame" src="{{ route('orders.profit_loss.stream', $order) }}" title="Laporan Laba Rugi"></iframe>

    <div class="preview-footer">
        <a class="btn btn-download" href="{{ route('orders.profit_loss.download', $order) }}">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Download Laporan L/R
        </a>
    </div>
</body>
</html>
