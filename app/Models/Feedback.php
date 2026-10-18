<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'name', 'contact', 'message'])]
class Feedback extends Model
{
    use HasFactory;

    public const TYPE_SUGGESTION = 'saran';

    public const TYPE_COMPLAINT = 'kritik';

    public const TYPE_APPRECIATION = 'apresiasi';

    public const TYPE_OTHER = 'lainnya';

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        self::TYPE_SUGGESTION => 'Saran',
        self::TYPE_COMPLAINT => 'Kritik',
        self::TYPE_APPRECIATION => 'Apresiasi',
        self::TYPE_OTHER => 'Lainnya',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
