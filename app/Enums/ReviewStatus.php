<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReviewStatus: string implements HasColor, HasLabel
{
    case PendingReview = 'pending_review';
    case RevisionRequested = 'revision_requested';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Menunggu Diperiksa',
            self::RevisionRequested => 'Perlu Revisi',
            self::Approved => 'Disetujui',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingReview => 'warning',
            self::RevisionRequested => 'danger',
            self::Approved => 'success',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return $this->color();
    }
}
