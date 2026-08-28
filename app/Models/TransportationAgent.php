<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'active'])]
class TransportationAgent extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
