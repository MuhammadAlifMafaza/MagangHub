<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GuidanceBookingStatus: string implements HasColor, HasLabel
{
    case Booked = 'booked';

    case Cancelled = 'cancelled';

    case Completed = 'completed';

    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Dipesan',
            self::Cancelled => 'Dibatalkan',
            self::Completed => 'Selesai',
            self::NoShow => 'Tidak Hadir',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Booked => 'info',
            self::Cancelled => 'danger',
            self::Completed => 'success',
            self::NoShow => 'warning',
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
