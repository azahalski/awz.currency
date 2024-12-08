<?php
namespace Awz\Currency\Access\Permission\Rules;

use Bitrix\Main\Access\AccessibleItem;
use Awz\Currency\Access\Custom\PermissionDictionary;

class RightView extends \Bitrix\Main\Access\Rule\AbstractRule
{
    public function execute(AccessibleItem $item = null, $params = null): bool
    {
        if ($this->user->isAdmin())
        {
            return true;
        }
        if ($this->user->getPermission(PermissionDictionary::MODULE_RIGHT_VIEW))
        {
            return true;
        }
        return false;
    }
}