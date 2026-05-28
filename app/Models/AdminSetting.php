<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type'
    ];

    /**
     * Get the value attribute with type casting.
     */
    public function getValueAttribute($value)
    {
        switch ($this->type) {
            case 'json':
                return json_decode($value, true);
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            default:
                return $value;
        }
    }

    /**
     * Set the value attribute with type handling.
     */
    public function setValueAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['value'] = json_encode($value);
            $this->attributes['type'] = 'json';
        } elseif (is_bool($value)) {
            $this->attributes['value'] = (int) $value;
            $this->attributes['type'] = 'boolean';
        } elseif (is_int($value)) {
            $this->attributes['value'] = $value;
            $this->attributes['type'] = 'integer';
        } else {
            $this->attributes['value'] = $value;
            $this->attributes['type'] = 'string';
        }
    }
}
