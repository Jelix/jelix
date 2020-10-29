<?php

class rightsCtrl extends jController
{
    public $pluginParams = array(
        'index' => array('jacl2.right' => 'acl.group.view'),
        'rights' => array('jacl2.rights.and' => array('acl.group.view', 'acl.group.modify')),
        'saverights' => array('jacl2.rights.and' => array('acl.group.view', 'acl.group.modify')),
        'newgroup' => array('jacl2.rights.and' => array('acl.group.view', 'acl.group.create')),
        'changename' => array('jacl2.rights.and' => array('acl.group.view', 'acl.group.modify')),
        'delgroup' => array('jacl2.rights.and' => array('acl.group.view', 'acl.group.delete')),
        'setdefault' => array('jacl2.rights.and' => array('acl.group.view', 'acl.group.modify')),
    );

    public function index()
    {
        $rep = $this->getResponse('html');

        $groups = array();

        $o = new StdClass();
        $o->id_aclgrp = '-2';
        $o->name = jLocale::get('jacl2db_admin~acl2.all.users.option');
        $o->grouptype = jAcl2DbUserGroup::GROUPTYPE_NORMAL;
        $groups[] = $o;

        $o = new StdClass();
        $o->id_aclgrp = '-1';
        $o->name = jLocale::get('jacl2db_admin~acl2.without.groups.option');
        $o->grouptype = jAcl2DbUserGroup::GROUPTYPE_NORMAL;
        $groups[] = $o;

        foreach (jAcl2DbUserGroup::getGroupList() as $grp) {
            $groups[] = $grp;
        }

        $manager = new jAcl2DbAdminUIManager();
        $listPageSize = 15;

        $type = $this->param('typeName', 'user');
        $offset = $this->param('idx', 0, true);
        $grpid = $this->param('grpid', jAcl2DbAdminUIManager::FILTER_GROUP_ALL_USERS, true);
        $filter = trim($this->param('filter'));
        $tpl = new jTpl();

        if ($type === 'user' && is_numeric($grpid) && intval($grpid) < 0) {
            $tpl->assign($manager->getUsersList($grpid, null, $filter, $offset, $listPageSize));
        } elseif ($type === 'user') {
            $tpl->assign($manager->getUsersList(jAcl2DbAdminUIManager::FILTER_BY_GROUP, $grpid, $filter, $offset, $listPageSize));
        } elseif ($type === 'group') {
            $tpl->assign($manager->getGroupByFilter($filter));
        } elseif ($type === 'all') {
            $usersResults = $manager->getUsersList($grpid, null, $filter, $offset, $listPageSize);
            $groupResults = $manager->getGroupByFilter($filter);
            jLog::dump($usersResults, 'users', 'error');
            $results = array(
                'results' => array_merge($usersResults['results'], $groupResults['results']),
                'resultsCount' => $usersResults['resultsCount'] + $groupResults['resultsCounts'],
            );
            jLog::dump($results, 'results', 'error');
            $tpl->assign($results);
        }

        $tpl->assign(compact('offset', 'grpid', 'listPageSize', 'groups', 'filter', 'type'));
        $rep->body->assign('MAIN', $tpl->fetch('users_list'));
        $rep->body->assign('selectedMenuItem', 'usersrights');

        return $rep;
    }
}
