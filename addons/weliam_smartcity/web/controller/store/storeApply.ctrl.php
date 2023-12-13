<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 商户入驻申请
 * Author: zzw
 * Date: 2021/1/7 15:35
 * Class StoreApply_WeliamController
 */
class StoreApply_WeliamController {
    //入驻申请列表
    public function index() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $where['uniacid'] = $_W['uniacid'];
        if($_W['aid'] > 0){
            $where['aid'] = $_W['aid'];
        }
        $where['ismain'] = 1;
        $where['status'] = array(0, 1,3);

        $registers = pdo_getslice('wlmerchant_merchantuser', $where, array($pindex, $psize), $total, array(), '', "id DESC");
        $pager = wl_pagination($total, $pindex, $psize);
        foreach ($registers as $key => $value) {
            $registers[$key]['member'] = Member::wl_member_get($value['mid'], array('id', 'nickname'));
            $registers[$key]['storedata'] = Store::getSingleStore($value['storeid']);
            if($value['aid'] > 0){
                $registers[$key]['agentname'] = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $value['aid']), 'agentname');
            }else{
                $registers[$key]['agentname'] = '总后台';
            }

        }

        include wl_template('store/registerIndex');
    }
    //对申请信息进行 审核、删除操作
    public function edit() {
        global $_W, $_GPC;
        if ($_GPC['op'] == 'reject') {
            if (pdo_update(PDO_NAME . 'merchantdata', array('status' => 3), array('id' => $_GPC['id']))) {
                Store::editSingleRegister($_GPC['uid'], array('status' => 3, 'reject' => $_GPC['reject']));
                $mid = pdo_getcolumn(PDO_NAME . 'merchantuser', array('id' => $_GPC['uid']), 'mid');
                $store = pdo_get(PDO_NAME . 'merchantdata', array('id' => $_GPC['id']), array('storename'));
                $storename = $store['storename'];
                $content = '商户名:' . $storename;
                $link = h5_url('pages/subPages/merchant/merchantChangeShop/merchantChangeShop');
                News::jobNotice($mid, '您好，您的商家入驻审核已完成', '商户入驻审核通知', $content, '未通过', '点击查看未通过原因', time(), $link);
                wl_message('驳回成功', referer(), 'succuss');
            } else {
                wl_message('驳回失败', referer(), 'error');
            }
        }
        if ($_GPC['op'] == 'pass') {
            $status = pdo_getcolumn(PDO_NAME . 'merchantdata',array('id' => $_GPC['id']),'status');
            if (pdo_update(PDO_NAME . 'merchantdata', array('status' => 2, 'enabled' => 1), array('id' => $_GPC['id']))) {
                if($status == 0){
                    //修改过期时间
                    $chargeid = pdo_getcolumn(PDO_NAME . 'order',array('sid' => $_GPC['id'],'status' => 0,'plugin' => 'store','payfor'=>'merchant'),'fkid');
                    $day = pdo_getcolumn(PDO_NAME . 'chargelist',array('id' => $chargeid),'days');
                    $endtime = time() + 86400*$day;
                    pdo_update(PDO_NAME . 'merchantdata', array('groupid' => $chargeid, 'endtime' => $endtime), array('id' => $_GPC['id']));
                    pdo_update('wlmerchant_order',array('status' => 3,'paytype' => 6 ,'paytime' => time(), 'issettlement' => 1,'price' => 0),array('sid' => $_GPC['id'],'plugin' => 'store'));
                }
                Store::editSingleRegister($_GPC['uid'], array('status' => 2, 'reject' => $_GPC['reject']));
                $member = pdo_get('wlmerchant_merchantuser', array('id' => $_GPC['uid']), array('mid'));
                $store = pdo_get('wlmerchant_merchantdata', array('id' => $_GPC['id']), array('storename'));
                $storename = $store['storename'];
                $content = '商户名:' . $storename;
                News::jobNotice($member['mid'], '您好，您的商家入驻审核已完成', '商户入驻审核通知', $content, '已通过', '请您尽快完善商家信息', time());
                wl_message('通过操作成功', web_url('store/merchant/index', array('enabled' => 1)), 'succuss');
            } else {
                wl_message('通过操作失败', referer(), 'error');
            }
        }
        if ($_GPC['op'] == 'del') {
            if (pdo_delete(PDO_NAME . 'merchantuser', array('id' => $_GPC['uid']))) {
                pdo_delete(PDO_NAME . 'merchantdata', array('id' => $_GPC['id']));
                wl_message('删除申请成功', referer(), 'succuss');
            } else {
                wl_message('删除申请失败', referer(), 'error');
            }
        }
    }


}
