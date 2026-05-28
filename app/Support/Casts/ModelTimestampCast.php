<?php

namespace App\Support\Casts;

use App\Support\DateFormatter;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ModelTimestampCast implements CastsAttributes
{
	public function get($model, string $key, $value, array $attributes)
    {
        if (empty($value) || $value === '0000-00-00 00:00:00') {
            return new DateFormatter(now()->toDateTimeString());
        }

        try {
            return new DateFormatter((string) $value);
        }
        catch (\Throwable $exception) {
            // Keep rendering resilient even if legacy rows contain malformed timestamps.
            return new DateFormatter(now()->toDateTimeString());
        }
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value instanceof DateFormatter) {
            return $value->getTimestamp();
        }
        
        return $value;
    }
}
