<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReportReviewDecision: string implements HasColor, HasLabel
{
    case RevisionRequested = 'revision_requested';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::RevisionRequested => 'Minta Revisi',
            self::Approved => 'Setujui',
        };
    }

    public function color(): string
    {
        return match ($this) {
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
