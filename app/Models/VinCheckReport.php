<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['vin', 'user_id', 'info', 'comment'])]
class VinCheckReport extends Model
{
    protected function casts(): array
    {
        return ['info' => 'array'];
    }
}
