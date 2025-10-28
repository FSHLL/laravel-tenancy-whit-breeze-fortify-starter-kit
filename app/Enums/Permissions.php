<?php

namespace App\Enums;

enum Permissions: string
{
    case CREATE_TENANT_USER_BY_TENANT = 'create tenant user by tenant';
    case VIEW_TENANT_USER_BY_TENANT = 'view tenant user by tenant';
    case UPDATE_TENANT_USER_BY_TENANT = 'update tenant user by tenant';
    case DELETE_TENANT_USER_BY_TENANT = 'delete tenant user by tenant';
}
