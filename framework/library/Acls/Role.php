<?php

namespace framework\library\Acls;

class Role extends \Model
{
    protected $table = 'roles';
    protected $pk = 'role_id';
    protected $permissions = [];

    /**
     * Get role's permissions.
     *
     * @return array
     */
    public function getPermissions()
    {
        if (empty($this->permissions)) {
            $this->loadRolePermissions();
        }

        return $this->permissions;
    }

    /**
     * @param \framework\library\Acls\Permission $permission
     *
     * @return bool
     */
    public function hasPermission(Permission $permission)
    {
        return in_array($permission, $this->getPermissions());
    }

    /**
     * Add permission to the role.
     *
     * @param \framework\library\Acls\Permission $permission
     */
    public function addPermission(Permission $permission)
    {
        if (!$this->hasPermission($permission)) {
            $data = [
        'role_id'       => $this->getId(),
        'permission_id' => $permission->getId(),
        ];
            $this->getDbo()->autocommit(false);
            try {
                $this->getDbo()->insert('roles_permissions', $data);
                $this->getDbo()->commit();
            } catch (\Exception $e) {
                $this->getDbo()->rollback();
            }
        }
    }

    /**
     * Remove permission.
     *
     * @param \framework\library\Acls\Permission $permission
     */
    public function removePermission(Permission $permission)
    {
        if ($this->hasPermission($permission)) {
            $this->getDbo()->autocommit(false);
            try {
                $whereClause = 'permission_id='.$permission->getId().' AND role_id='.$this->getId();
                $this->getDbo()->delete('roles_permissions', $whereClause);
                $this->getDbo()->commit();
            } catch (\Exception $e) {
                $this->getDbo()->rollback();
            }
        }

        return $this;
    }

    /**
     * Reset permission.
     *
     * @return \framework\library\Acls\Role
     */
    public function resetRolePermissions()
    {
        $this->getDbo()->autocommit(false);
        try {
            $whereClause = 'role_id='.$this->getId();
            $this->getDbo()->delete('roles_permissions', $whereClause);
            $this->getDbo()->commit();
        } catch (\Exception $e) {
            $this->getDbo()->rollback();
        }

        return $this;
    }

    /**
     * Load the user_roles.
     */
    protected function loadRolePermissions()
    {
        $this->getDbo()->clear()->select('perm.*')
        ->from('`permissions` as perm')
        ->join('`roles_permissions` as rp', 'rp.permission_id = perm.id')
        ->where('rp.role_id='.$this->getId())
        ->buildQuery()
        ->execute();
        $rows = $this->getDbo()->fetchAssocList();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $this->permissions[] = new Permission($row);
            }
        }
    }
}
