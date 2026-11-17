<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'reservation_date', 'reservation_time', 'guests', 'status', 'notes'])]
class Reservation extends Model
{
    public const AVAILABLE_TIMES = ['11:00', '12:00', '13:00', '14:00', '17:00', '18:00', '19:00', '20:00'];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function slotHasPassed(): bool
    {
        return static::isSlotInPast($this->reservation_date->toDateString(), substr((string) $this->reservation_time, 0, 5));
    }

    public static function isSlotInPast(string $date, string $time): bool
    {
        return Carbon::parse($date.' '.substr($time, 0, 5))->isPast();
    }

    public static function hasActiveSlotConflict(int $userId, string $date, string $time, ?int $ignoreId = null): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->whereDate('reservation_date', $date)
            ->whereTime('reservation_time', '=', substr($time, 0, 5).':00')
            ->whereIn('status', ['pending', 'confirmed'])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
