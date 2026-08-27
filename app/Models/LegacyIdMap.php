<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['entity', 'old_id', 'new_id'])]
class LegacyIdMap extends Model
{
    protected $table = 'legacy_id_map';
}
