<?php

namespace framework\library\Acls;

use Kazinduzi\Core\Kazinduzi;

class Permission extends \Model
{
    const PERM_TABLE_NAME = 'permissions';
    protected $table = self::PERM_TABLE_NAME;
    protected $pk = 'id';

    /**
     * @param string $perm
     *
     * @return type
     */
    public static function getByName($perm)
    {
        $options = [
        'WHERE' => sprintf('name=\'%s\'', Kazinduzi::db()->real_escape_string($perm)),
        'LIMIT' => 1,
    ];
        $perms = parent::find(self::PERM_TABLE_NAME, $options);
        if (!empty($perms)) {
            return $perms[0];
        }
    }
}
