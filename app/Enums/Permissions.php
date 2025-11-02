<?php

namespace App\Enums;

enum Permissions: string
{
    case CREATE_TENANT_USER_BY_TENANT = 'create tenant user by tenant';
    case VIEW_TENANT_USER_BY_TENANT = 'view tenant user by tenant';
    case UPDATE_TENANT_USER_BY_TENANT = 'update tenant user by tenant';
    case DELETE_TENANT_USER_BY_TENANT = 'delete tenant user by tenant';

    case CREATE_ROLE_BY_TENANT = 'create role by tenant';
    case VIEW_ROLE_BY_TENANT = 'view role by tenant';
    case UPDATE_ROLE_BY_TENANT = 'update role by tenant';
    case DELETE_ROLE_BY_TENANT = 'delete role by tenant';
}
