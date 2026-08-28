<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'active', 'country_id', 'is_default', 'sort'])]
class Auction extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
