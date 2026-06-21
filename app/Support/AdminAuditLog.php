<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class AdminAuditLog
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function record(string $action, array $context = []): void
    {
        Log::info("Admin action: {$action}", array_merge($context, [
            'admin_id' => auth()->id(),
            'admin_email' => auth()->user()?->email,
            'ip' => request()->ip(),
        ]));
    }
}
