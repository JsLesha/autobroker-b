<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['provider', 'direction', 'status', 'payload', 'error'])]
class IntegrationLog extends Model
{
    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
