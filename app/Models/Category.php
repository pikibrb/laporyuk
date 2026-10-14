<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'urgency_level'])]
class Category extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}