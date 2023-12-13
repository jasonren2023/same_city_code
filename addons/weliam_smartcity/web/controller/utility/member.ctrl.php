<?php
defined('IN_IA') or exit('Access Denied');

class Member_WeliamController {

    public function mc_select() {
        global $_W, $_GPC;
        //参数信息获取
        $search = $_GPC['search'] ? : '';
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} ";
        if($search) $where .= " AND (nickname like '%{$search}%' OR mobile like '%{$search}%' OR id like '%{$search}%')";
        //信息查询
        $sql = "SELECT id,nickname,mobile FROM ".tablename(PDO_NAME."member");
        $members = pdo_fetchall($sql.$where." LIMIT 10");
        foreach ($members as &$member) {
            $data[] = ['id' => $member['id'], 'text' => $member['nickname'].'(MID:'. $member['id'].')'.($member['mobile'] ? ' - ' . $member['mobile'] : '')];
        }
        die(json_encode($data));
    }
}