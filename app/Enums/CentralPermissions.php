<?php

namespace App\Enums;

enum CentralPermissions: string
{
    case CREATE_TENANT = 'create tenant';
    case VIEW_TENANT = 'view tenant';
    case UPDATE_TENANT = 'update tenant';
    case DELETE_TENANT = 'delete tenant';

    case CREATE_TENANT_USER = 'create tenant user';
    case VIEW_TENANT_USER = 'view tenant user';
    case UPDATE_TENANT_USER = 'update tenant user';
    case DELETE_TENANT_USER = 'delete tenant user';
}
