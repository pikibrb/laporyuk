<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'name', 'contact', 'message', 'response', 'responded_by', 'responded_at'])]
class Feedback extends Model
{
    use HasFactory;

    public const TYPE_SUGGESTION = 'saran';

    public const TYPE_COMPLAINT = 'kritik';

    public const TYPE_APPRECIATION = 'apresiasi';

    public const TYPE_OTHER = 'lainnya';

    public const TYPE_LABELS = [
        self::TYPE_SUGGESTION => 'Saran',
        self::TYPE_COMPLAINT => 'Kritik',
        self::TYPE_APPRECIATION => 'Apresiasi',
        self::TYPE_OTHER => 'Lainnya',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function isResponded(): bool
    {
        return $this->responded_at !== null;
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => __(self::TYPE_LABELS[$this->type] ?? ucfirst($this->type)),
        );
    }
}
