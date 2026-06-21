<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px 8px;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:#6366f1;">
                                {{ $siteName ?? config('app.name') }}
                            </p>
                            <h1 style="margin:0;font-size:22px;line-height:1.3;color:#0f172a;">{{ $heading }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 32px 24px;">
                            <p style="margin:0;font-size:15px;line-height:1.6;color:#475569;white-space:pre-line;">{{ $messageText }}</p>
                        </td>
                    </tr>
                    @if(!empty($actionUrl))
                        <tr>
                            <td style="padding:0 32px 32px;">
                                <a href="{{ $actionUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 20px;border-radius:12px;">
                                    {{ $actionLabel ?? 'View ticket' }}
                                </a>
                            </td>
                        </tr>
                    @endif
                </table>
                <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;">
                    You received this because of activity on your {{ $siteName ?? config('app.name') }} account.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
