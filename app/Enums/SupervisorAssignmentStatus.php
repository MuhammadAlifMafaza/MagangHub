<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SupervisorAssignmentStatus: string implements HasColor, HasLabel
{
    case Active = 'active';

    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Ended => 'Berakhir',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Ended => 'neutral',
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
