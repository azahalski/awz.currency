<?php
namespace Awz\Currency\Access\Tables;

use Bitrix\Main\Access\Role\AccessRoleTable;

class RoleTable extends AccessRoleTable
{
    public static function getTableName()
    {
        return 'awz_currency_role';
    }
}