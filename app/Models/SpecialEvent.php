<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpecialEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'country',
        'keywords',
        'start_date',
        'end_date',
        'status',
        'boost_factor',
        'context_prompt'
    ];

    protected $casts = [
        'keywords' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'boost_factor' => 'float',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCountry($query, $country)
    {
        return $query->where(function($q) use ($country) {
            $q->where('country', $country)->orWhereNull('country');
        });
    }
}
