<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as Father;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Permission extends Father
{
    use BelongsToTenant;
}
