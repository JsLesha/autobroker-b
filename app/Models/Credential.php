<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['auction_id', 'user_id', 'counterparty_id', 'login', 'secret', 'buyer_code', 'active'])]
#[Hidden(['secret'])]
class Credential extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'active' => 'boolean',
        ];
    }
}
