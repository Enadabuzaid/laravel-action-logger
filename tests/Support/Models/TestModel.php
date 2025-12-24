<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Support\Models;

use Enadstack\ActionLogger\Traits\HasActionLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestModel extends Model
{
    use HasActionLogs;
    use SoftDeletes;

    protected $table = 'test_models';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
