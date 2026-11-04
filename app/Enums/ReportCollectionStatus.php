<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReportCollectionStatus: string implements HasColor, HasLabel
{
    case NotOpened = 'not_opened';

    case NotSubmitted = 'not_submitted';

    case NotSubmittedLate = 'not_submitted_late';

    case Submitted = 'submitted';

    case SubmittedLate = 'submitted_late';

    public function label(): string
    {
        return match ($this) {
            self::NotOpened => 'Belum Dibuka',
            self::NotSubmitted => 'Belum Dikumpulkan',
            self::NotSubmittedLate => 'Belum Dikumpulkan — Melewati Batas',
            self::Submitted => 'Dikumpulkan',
            self::SubmittedLate => 'Dikumpulkan — Terlambat',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotOpened => 'gray',
            self::NotSubmitted => 'warning',
            self::NotSubmittedLate => 'danger',
            self::Submitted => 'info',
            self::SubmittedLate => 'warning',
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
