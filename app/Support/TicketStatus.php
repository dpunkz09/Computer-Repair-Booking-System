<?php

namespace App\Support;

class TicketStatus
{
    public const NEW = 'new';

    public const ASSIGNED = 'assigned';

    public const IN_PROGRESS = 'in_progress';

    public const AWAITING_PARTS = 'awaiting_parts';

    public const RESOLVED = 'resolved';

    public const CLOSED = 'closed';

    public const CANCELLED_FILTER = 'cancelled';

    /** @var array<int, string> */
    public const WORKFLOW = [
        self::NEW,
        self::ASSIGNED,
        self::IN_PROGRESS,
        self::AWAITING_PARTS,
        self::RESOLVED,
        self::CLOSED,
    ];

    /** @var array<int, string> */
    public const FILTERABLE = [
        ...self::WORKFLOW,
        self::CANCELLED_FILTER,
    ];

    /** @var array<int, string> */
    public const TECHNICIAN_QUICK_UPDATE = [
        self::ASSIGNED,
        self::IN_PROGRESS,
        self::AWAITING_PARTS,
        self::RESOLVED,
        self::CLOSED,
    ];

    /** @var array<int, string> */
    public const OPEN = [
        self::NEW,
        self::ASSIGNED,
        self::IN_PROGRESS,
        self::AWAITING_PARTS,
    ];

    /** @var array<int, string> */
    public const TERMINAL = [
        self::RESOLVED,
        self::CLOSED,
    ];

    public static function label(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }

    public static function ruleInWorkflow(): string
    {
        return 'in:'.implode(',', self::WORKFLOW);
    }

    public static function ruleInFilterable(): string
    {
        return 'in:'.implode(',', self::FILTERABLE);
    }

    public static function ruleInTechnicianQuickUpdate(): string
    {
        return 'in:'.implode(',', self::TECHNICIAN_QUICK_UPDATE);
    }
}
