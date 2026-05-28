<?php

namespace App\Models;

use App\Database\Configs\Table;
use Illuminate\Database\Eloquent\Model;

class UserBlock extends Model
{
    public $table = Table::USER_BLOCKS;

    public $guarded = [];

    public function blocker()
    {
        return $this->belongsTo(User::class, 'blocker_id', 'id');
    }

    public function blocked()
    {
        return $this->belongsTo(User::class, 'blocked_id', 'id');
    }
}
