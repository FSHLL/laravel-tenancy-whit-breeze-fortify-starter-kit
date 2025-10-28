<?php

namespace App\Models;

use Spatie\Permission\Models\Role as Father;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Role extends Father
{
    use BelongsToTenant;
}
