<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2006-2019 Laurent Jouanneau
 *
 * @see        http://www.jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 *
 * @since 1.1
 */

/**
 * This class is used to manage rights. Works only with db driver of jAcl2.
 *
 * @static
 */
class jAcl2DbManager
{
    public static $ACL_ADMIN_RIGHTS = array(
        'acl.group.view',
        'acl.group.modify',
        'acl.group.delete',
        'acl.user.view',
        'acl.user.modify',
    );

    /**
     * @internal The constructor is private, because all methods are static
     */
    private function __construct()
    {
    }

    /**
     * add a right on the given role/group/resource.
     *
     * @param string $group    the group id
     * @param string $role     the key of the role
     * @param string $resource the id of a resource
     *
     * @return bool true if the right is set
     */
    public static function addRight($group, $role, $resource = '-')
    {
        $sbj = jDao::get('jacl2db~jacl2subject', 'jacl2_profile')->get($role);
        if (!$sbj) {
            return false;
        }

        if (empty($resource)) {
            $resource = '-';
        }

        //  add the new value
        $daoright = jDao::get('jacl2db~jacl2rights', 'jacl2_profile');
        $right = $daoright->get($role, $group, $resource);
        if (!$right) {
            $right = jDao::createRecord('jacl2db~jacl2rights', 'jacl2_profile');
            $right->id_aclsbj = $role;
            $right->id_aclgrp = $group;
            $right->id_aclres = $resource;
            $right->canceled = 0;
            $daoright->insert($right);
        } elseif ($right->canceled) {
            $right->canceled = false;
            $daoright->update($right);
        }
        jAcl2::clearCache();

        return true;
    }

    /**
     * remove a right on the given role/group/resource. The given right for this group will then
     * inherit from other groups if the user is in multiple groups of users.
     *
     * @param string $group    the group id
     * @param string $role     the key of the role
     * @param string $resource the id of a resource
     * @param bool   $canceled true if the removing is to cancel a right, instead of an inheritance
     */
    public static function removeRight($group, $role, $resource = '-', $canceled = false)
    {
        if (empty($resource)) {
            $resource = '-';
        }

        $daoright = jDao::get('jacl2db~jacl2rights', 'jacl2_profile');
        if ($canceled) {
            $right = $daoright->get($role, $group, $resource);
            if (!$right) {
                $right = jDao::createRecord('jacl2db~jacl2rights', 'jacl2_profile');
                $right->id_aclsbj = $role;
                $right->id_aclgrp = $group;
                $right->id_aclres = $resource;
                $right->canceled = $canceled;
                $daoright->insert($right);
            } elseif ($right->canceled != $canceled) {
                $right->canceled = $canceled;
                $daoright->update($right);
            }
        } else {
            $daoright->delete($role, $group, $resource);
        }
        jAcl2::clearCache();
    }

    /**
     * Set all rights on the given group.
     *
     * Only rights on given roles are changed.
     * Existing rights not given in parameters are deleted from the group (i.e: marked as inherited).
     *
     * Rights with resources are not changed.
     *
     * @param string $group  the group id
     * @param array  $rights list of rights key=role, value=false(inherit)/''(inherit)/true(add)/'y'(add)/'n'(remove)
     */
    public static function setRightsOnGroup($group, $rights)
    {
        $subjects = jDao::get('jacl2db~jacl2subject', 'jacl2_profile')->findAllSubject()->fetchAll();
        $dao = jDao::get('jacl2db~jacl2rights', 'jacl2_profile');

        // retrieve old rights.
        $oldrights = array();
        $rs = $dao->getRightsByGroup($group);
        foreach ($rs as $rec) {
            $oldrights[$rec->id_aclsbj] = ($rec->canceled ? 'n' : 'y');
        }

        $roots = array();
        foreach ($subjects as $subject) {
            $matches = array();
            if (preg_match('/(.*)(\.view)$/', $subject->id_aclsbj, $matches)) {
                $roots[] = $matches[1];
            }
        }

        // set new rights.  we modify $oldrights in order to have
        // only deprecated rights in $oldrights
        foreach ($rights as $sbj => $val) {
            if ($val === '' || $val == false) {
                // remove
            } elseif ($val === true || $val == 'y') {
                foreach ($roots as $root) {
                    if (preg_match('/^('.$root.'.)/', $sbj)) {
                        self::addRight($group, $root.'.view');
                    }
                }
                self::addRight($group, $sbj);
                unset($oldrights[$sbj]);
            } elseif ($val == 'n') {
                // cancel
                $matches = array();
                if (preg_match('/(.*)(\.view)$/', $sbj, $matches)) {
                    foreach ($subjects as $subject) {
                        if (preg_match('/^('.$matches[1].'.)/', $sbj)) {
                            self::removeRight($group, $subject, '-', true);
                        }
                    }
                }
                if (isset($oldrights[$sbj])) {
                    unset($oldrights[$sbj]);
                }
                self::removeRight($group, $sbj, '', true);
            }
        }

        if (count($oldrights)) {
            // $oldrights contains now rights to remove
            $dao->deleteByGroupAndRoles($group, array_keys($oldrights));
        }
        jAcl2::clearCache();
    }

    /**
     * remove the right on the given role/resource, for all groups.
     *
     * @param string $role     the key of the role
     * @param string $resource the id of a resource
     */
    public static function removeResourceRight($role, $resource)
    {
        if (empty($resource)) {
            $resource = '-';
        }
        jDao::get('jacl2db~jacl2rights', 'jacl2_profile')->deleteByRoleRes($role, $resource);
        jAcl2::clearCache();
    }

    /**
     * create a new role.
     *
     * @param string $role         the key of the role
     * @param string $label_key    the key of a locale which represents the label of the role
     * @param string $subjectGroup the id of the group where the role is attached to
     *
     * @since 1.7
     */
    public static function addRole($role, $label_key, $subjectGroup = null)
    {
        $dao = jDao::get('jacl2db~jacl2subject', 'jacl2_profile');
        if ($dao->get($role)) {
            return;
        }
        $subj = jDao::createRecord('jacl2db~jacl2subject', 'jacl2_profile');
        $subj->id_aclsbj = $role;
        $subj->label_key = $label_key;
        $subj->id_aclsbjgrp = $subjectGroup;
        $dao->insert($subj);
        jAcl2::clearCache();
    }

    /**
     * create a new role.
     *
     * @deprecated
     * @see addRole()
     *
     * @param string $role         the key of the role
     * @param string $label_key    the key of a locale which represents the label of the role
     * @param string $subjectGroup the id of the group where the role is attached to
     */
    public static function addSubject($role, $label_key, $subjectGroup = null)
    {
        self::addRole($role, $label_key, $subjectGroup);
    }

    /**
     * Delete the given role.
     *
     * @param string $role the key of the role
     *
     * @since 1.7
     */
    public static function removeRole($role)
    {
        jDao::get('jacl2db~jacl2rights', 'jacl2_profile')->deleteByRole($role);
        jDao::get('jacl2db~jacl2subject', 'jacl2_profile')->delete($role);
        jAcl2::clearCache();
    }

    /**
     * Delete the given role.
     *
     * @param string $role the key of the role
     *
     * @deprecated see removeRole()
     */
    public static function removeSubject($role)
    {
        self::removeRole($role);
    }

    /**
     * set same rights with a specific role, on groups having an other specific role.
     *
     * It can be useful when creating a new role.
     *
     * @param string $fromRole     the role of the role
     * @param string $label_key    the key of a locale which represents the label of the role
     * @param string $subjectGroup the id of the group where the role is attached to
     * @param mixed  $toRole
     *
     * @since 1.7
     */
    public static function copyRoleRights($fromRole, $toRole)
    {
        $daoright = jDao::get('jacl2db~jacl2rights', 'jacl2_profile');

        $allRights = $daoright->getRightsByRole($fromRole);
        foreach ($allRights as $right) {
            $rightTo = $daoright->get($toRole, $right->id_aclgrp, $right->id_aclres);
            if (!$rightTo) {
                $rightTo = jDao::createRecord('jacl2db~jacl2rights', 'jacl2_profile');
                $rightTo->id_aclsbj = $toRole;
                $rightTo->id_aclgrp = $right->id_aclgrp;
                $rightTo->id_aclres = $right->id_aclres;
                $rightTo->canceled = $right->canceled;
                $daoright->insert($rightTo);
            } elseif ($right->canceled != $rightTo->canceled) {
                $rightTo->canceled = $right->canceled;
                $daoright->update($rightTo);
            }
        }

        jAcl2::clearCache();
    }

    /**
     * Create a new role group.
     *
     * @param string $roleGroup the key of the role group
     * @param string $label_key the key of a locale which represents the label of the role group
     *
     * @since 1.7
     */
    public static function addRoleGroup($roleGroup, $label_key)
    {
        $dao = jDao::get('jacl2db~jacl2subjectgroup', 'jacl2_profile');
        if ($dao->get($roleGroup)) {
            return;
        }
        $subj = jDao::createRecord('jacl2db~jacl2subjectgroup', 'jacl2_profile');
        $subj->id_aclsbjgrp = $roleGroup;
        $subj->label_key = $label_key;
        $dao->insert($subj);
        jAcl2::clearCache();
    }

    /**
     * Create a new role group.
     *
     * @param string $roleGroup the key of the role group
     * @param string $label_key the key of a locale which represents the label of the role group
     *
     * @since 1.3
     * @deprecated see addRoleGroup()
     */
    public static function addSubjectGroup($roleGroup, $label_key)
    {
        self::addRoleGroup($roleGroup, $label_key);
    }

    /**
     * Delete the given role.
     *
     * @param string $roleGroup the key of the role group
     *
     * @since 1.7
     */
    public static function removeRoleGroup($roleGroup)
    {
        jDao::get('jacl2db~jacl2subject', 'jacl2_profile')->removeSubjectFromGroup($roleGroup);
        jDao::get('jacl2db~jacl2subjectgroup', 'jacl2_profile')->delete($roleGroup);
        jAcl2::clearCache();
    }

    /**
     * Delete the role subject.
     *
     * @param string $roleGroup the key of the role group
     *
     * @since 1.3
     * @deprecated see removeRoleGroup
     */
    public static function removeSubjectGroup($roleGroup)
    {
        self::removeRoleGroup($roleGroup);
    }

    const ACL_ADMIN_RIGHTS_STILL_USED = 0;
    const ACL_ADMIN_RIGHTS_NOT_ASSIGNED = 1;
    const ACL_ADMIN_RIGHTS_SESSION_USER_LOOSE_THEM = 2;

    /**
     * Only rights on given roles are considered changed.
     * Existing rights not given in parameters are considered as deleted.
     *
     * Rights with resources are not changed.
     *
     * @param array      $rightsChanges         array(<id_aclgrp> => array( <role> => false(inherit)/''(inherit)/true(add)/'y'(add)/'n'(remove)))
     * @param null|mixed $sessionUser
     * @param mixed      $setForAllPublicGroups
     * @param mixed      $setAllRightsInGroups
     * @param null|mixed $ignoredUser
     * @param null|mixed $ignoreUserInGroup
     *
     * @return int one of the ACL_ADMIN_RIGHTS_* const
     */
    public static function checkAclAdminRightsChanges(
        $rightsChanges,
        $sessionUser = null,
        $setForAllPublicGroups = true,
        $setAllRightsInGroups = true,
        $ignoredUser = null,
        $ignoreUserInGroup = null
    ) {
        $canceledRoles = array();
        $assignedRoles = array();
        $sessionUserGroups = array();
        $sessionCanceledRoles = array();
        $sessionAssignedRoles = array();

        $db = jDb::getConnection('jacl2_profile');
        if ($sessionUser) {
            $gp = jDao::get('jacl2db~jacl2usergroup', 'jacl2_profile')
                ->getGroupsUser($sessionUser)
            ;
            foreach ($gp as $g) {
                $sessionUserGroups[$g->id_aclgrp] = true;
            }
        }

        // get all acl admin rights, even all those in private groups
        $sql = 'SELECT id_aclsbj, r.id_aclgrp, canceled, g.grouptype
            FROM '.$db->prefixTable('jacl2_rights').' r 
            INNER JOIN '.$db->prefixTable('jacl2_group').' g 
            ON (r.id_aclgrp = g.id_aclgrp)
            WHERE id_aclsbj IN ('.implode(',', array_map(function ($role) use ($db) {
            return $db->quote($role);
        }, self::$ACL_ADMIN_RIGHTS)).') ';
        $rs = $db->query($sql);
        foreach ($rs as $rec) {
            if ($sessionUser && isset($sessionUserGroups[$rec->id_aclgrp])) {
                if ($rec->canceled != '0') {
                    $sessionCanceledRoles[$rec->id_aclsbj] = true;
                } else {
                    $sessionAssignedRoles[$rec->id_aclsbj] = true;
                }
            }
            if ($setForAllPublicGroups &&
                !isset($rightsChanges[$rec->id_aclgrp]) &&
                $rec->grouptype != jAcl2DbUserGroup::GROUPTYPE_PRIVATE
            ) {
                continue;
            }
            if ($rec->canceled != '0') {
                $canceledRoles[$rec->id_aclgrp][$rec->id_aclsbj] = true;
            } else {
                $assignedRoles[$rec->id_aclgrp][$rec->id_aclsbj] = true;
            }
        }

        $rolesStats = array_combine(self::$ACL_ADMIN_RIGHTS, array_fill(0, count(self::$ACL_ADMIN_RIGHTS), 0));

        // now apply changes
        foreach ($rightsChanges as $groupId => $changes) {
            if (!isset($assignedRoles[$groupId])) {
                $assignedRoles[$groupId] = array();
            }
            if (!isset($canceledRoles[$groupId])) {
                $canceledRoles[$groupId] = array();
            }
            $unassignedRoles = array_combine(self::$ACL_ADMIN_RIGHTS, array_fill(0, count(self::$ACL_ADMIN_RIGHTS), true));
            foreach ($changes as $role => $roleAssignation) {
                if (!isset($rolesStats[$role])) {
                    continue;
                }
                unset($unassignedRoles[$role]);
                if ($roleAssignation === false || $roleAssignation === '') {
                    // inherited
                    if (isset($assignedRoles[$groupId][$role])) {
                        unset($assignedRoles[$groupId][$role]);
                    }
                    if (isset($canceledRoles[$groupId][$role])) {
                        unset($canceledRoles[$groupId][$role]);
                    }
                } elseif ($roleAssignation == 'y' || $roleAssignation === true) {
                    if (isset($canceledRoles[$groupId][$role])) {
                        unset($canceledRoles[$groupId][$role]);
                    }
                    $assignedRoles[$groupId][$role] = true;
                } elseif ($roleAssignation == 'n') {
                    if (isset($assignedRoles[$groupId][$role])) {
                        unset($assignedRoles[$groupId][$role]);
                    }
                    $canceledRoles[$groupId][$role] = true;
                }
            }
            if ($setAllRightsInGroups) {
                foreach ($unassignedRoles as $role => $ok) {
                    if (isset($assignedRoles[$groupId][$role])) {
                        unset($assignedRoles[$groupId][$role]);
                    }
                    if (isset($canceledRoles[$groupId][$role])) {
                        unset($canceledRoles[$groupId][$role]);
                    }
                }
            }
            if (count($assignedRoles[$groupId]) == 0 && count($canceledRoles[$groupId]) == 0) {
                unset($assignedRoles[$groupId], $canceledRoles[$groupId]);
            }
        }

        // get all users that are in groups having new acl admin rights
        $allGroups = array_unique(array_merge(array_keys($assignedRoles), array_keys($canceledRoles)));
        if (count($allGroups) === 0) {
            return self::ACL_ADMIN_RIGHTS_NOT_ASSIGNED;
        }

        $sql = 'SELECT login, id_aclgrp FROM '.$db->prefixTable('jacl2_user_group').'
            WHERE id_aclgrp IN ('.implode(',', array_map(function ($grp) use ($db) {
            return $db->quote($grp);
        }, $allGroups)).') ';

        $rs = $db->query($sql);
        $users = array();
        foreach ($rs as $rec) {
            if ($rec->login === $ignoredUser &&
                ($ignoreUserInGroup === null || $ignoreUserInGroup === $rec->id_aclgrp)) {
                continue;
            }
            if (!isset($users[$rec->login])) {
                $users[$rec->login] = array('canceled' => array(), 'roles' => array());
            }
            if (isset($assignedRoles[$rec->id_aclgrp])) {
                $users[$rec->login]['roles'] = array_merge($users[$rec->login]['roles'], $assignedRoles[$rec->id_aclgrp]);
            }
            if (isset($canceledRoles[$rec->id_aclgrp])) {
                $users[$rec->login]['canceled'] = array_merge($users[$rec->login]['canceled'], $canceledRoles[$rec->id_aclgrp]);
            }
        }

        // gets statistics
        $newSessionUserRoles = array();
        foreach ($users as $login => $data) {
            if (count($data['canceled'])) {
                $data['roles'] = array_diff_key($data['roles'], $data['canceled']);
            }
            if ($login === $sessionUser) {
                $newSessionUserRoles = $data['roles'];
            }
            foreach ($data['roles'] as $role => $ok) {
                ++$rolesStats[$role];
            }
        }

        if ($sessionUser) {
            foreach ($sessionAssignedRoles as $role => $ok) {
                if (isset($sessionCanceledRoles[$role])) {
                    continue;
                }
                if (!isset($newSessionUserRoles[$role])) {
                    return self::ACL_ADMIN_RIGHTS_SESSION_USER_LOOSE_THEM;
                }
            }
        }

        foreach ($rolesStats as $count) {
            if ($count == 0) {
                return self::ACL_ADMIN_RIGHTS_NOT_ASSIGNED;
            }
        }

        return self::ACL_ADMIN_RIGHTS_STILL_USED;
    }
}
