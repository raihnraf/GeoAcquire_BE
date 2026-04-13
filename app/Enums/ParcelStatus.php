<?php

namespace App\Enums;

enum ParcelStatus: string
{
    case Free = 'free';
    case Negotiating = 'negotiating';
    case Target = 'target';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    public static function fromString(string $value): self
    {
        return match ($value) {
            'free' => self::Free,
            'negotiating' => self::Negotiating,
            'target' => self::Target,
            default => throw new \InvalidArgumentException("Invalid parcel status: {$value}"),
        };
    }
}
