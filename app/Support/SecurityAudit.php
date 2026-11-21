<?php

namespace App\Support;

use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityAudit
{
    public static function record(string $event, ?User $user, Request $request, array $metadata = []): void
    {
        SecurityAuditLog::create([
            'user_id' => $user?->id,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'metadata' => $metadata ?: null,
        ]);
    }
}
