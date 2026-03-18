<?php

namespace App\Enums\Enquiry;

enum Status: string
{
    // Define possible statuses for an enquiry (Approved, Rejected, Pending)
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Pending = 'pending';

    // Method to get a human-readable label for each status
    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Pending => 'Pending',
        };
    }
}