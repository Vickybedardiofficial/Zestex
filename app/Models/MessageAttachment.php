<?php

namespace App\Models;

use App\Database\Configs\Table;
use Illuminate\Database\Eloquent\Model;

class MessageAttachment extends Model
{
    public $table = Table::MESSAGE_ATTACHMENTS;

    public $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
