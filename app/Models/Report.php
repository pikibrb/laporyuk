<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'category_id', 'title', 'description', 'phone', 'photo_path', 'latitude', 'longitude', 'status'])]
class Report extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_REJECTED = 'rejected';

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_PROCESSING => 'Diproses',
        self::STATUS_RESOLVED => 'Selesai',
        self::STATUS_REJECTED => 'Ditolak',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Report $report): void {
            $report->uuid ??= (string) Str::uuid();
        });

        static::created(function (Report $report): void {
            $report->statusHistories()->create([
                'user_id' => $report->user_id,
                'status' => $report->status,
                'notes' => 'Laporan diterima dan menunggu verifikasi petugas.',
            ]);
        });
    }

    /**
     * Use the unguessable uuid in URLs instead of the sequential primary key.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class);
    }

    /**
     * @param  array{search?: string|null, status?: string|null, category?: string|null}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, function (Builder $query, string $status): void {
                $query->where('status', $status);
            })
            ->when($filters['category'] ?? null, function (Builder $query, string $categoryId): void {
                $query->where('category_id', $categoryId);
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            });
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => __(self::STATUS_LABELS[$this->status] ?? ucfirst($this->status)),
        );
    }

    /**
     * Evidence photos are streamed by the application so the portal does not
     * depend on a public storage symlink being present on the server.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->photo_path) {
                return null;
            }

            return Storage::disk('public')->exists($this->photo_path)
                ? route('laporan.foto', $this)
                : null;
        });
    }

    /**
     * Tailwind classes used by the map marker for the current status.
     */
    public function markerColor(): string
    {
        return match ($this->status) {
            self::STATUS_PROCESSING => '#1d4ed8',
            self::STATUS_RESOLVED => '#059669',
            self::STATUS_REJECTED => '#6b7280',
            default => '#d97706',
        };
    }

    /**
     * Short, human readable age used across the portal lists.
     */
    public function createdAgo(): string
    {
        $minutes = (int) $this->created_at->diffInMinutes();

        return match (true) {
            $minutes < 1 => __('Baru saja'),
            $minutes < 60 => __(':count menit lalu', ['count' => $minutes]),
            $minutes < 1440 => __(':count jam lalu', ['count' => intdiv($minutes, 60)]),
            $minutes < 2880 => __('Kemarin'),
            default => $this->created_at->format('d M Y'),
        };
    }

    /**
     * Short public reference shown to warga instead of the internal id.
     */
    public function reference(): string
    {
        return strtoupper(substr((string) $this->uuid, 0, 8));
    }

    /**
     * @return array{title: string, category: string, status: string, status_label: string, latitude: float, longitude: float, color: string, url: string, created_ago: string}
     */
    public function toMapMarker(): array
    {
        return [
            'title' => $this->title,
            'category' => $this->category?->name ?? 'Tanpa kategori',
            'status' => $this->status,
            'status_label' => $this->status_label,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'color' => $this->markerColor(),
            'url' => route('laporan.show', $this),
            'created_ago' => $this->createdAgo(),
        ];
    }
}
