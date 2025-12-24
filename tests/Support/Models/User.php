<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Tests\Support\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
