<?php
defined('IN_IA') or exit('Access Denied');

class mrecharge_WeliamController {


    /**
     * Comment: 话费充值订单
     * Author: wlf
     * Date: 2021/11/03 16:09
     */
    public function orderList(){
        global $_W,$_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $where = array();
        $where['uniacid'] = $_W['uniacid'];

        if(empty($_GPC['status'])){
            $where['status>'] = 1;
        }else{
            $where['status'] = $_GPC['status'];
        }
        if($_GPC['keyword']){
            $keyword = $_GPC['keyword'];
            if($_GPC['keywordtype'] == 1){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where['mid#'] = $mids;
                }else{
                    $where['mid'] = 0;
                }
            }else if($_GPC['keywordtype'] == 2){
                $where['mobile@'] = "%{$keyword}%";
            }
        }

        if($_GPC['time_limit']){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if($_GPC['timetype'] == 1){
                $where['paytime>'] = $starttime;
                $where['paytime<'] = $endtime+86400;
            }else if($_GPC['timetype'] == 2){
                $where['finishtime>'] = $starttime;
                $where['finishtime<'] = $endtime+86400;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        if(!empty($_GPC['export'])){
            $this -> export($where);
        }

        $payonlinelist = Util::getNumData('*','wlmerchant_mrecharge_order',$where,'paytime DESC',$pindex,$psize,1);
        $pager = $payonlinelist[1];
        $list = $payonlinelist[0];
        foreach ($list as $key => &$li) {
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('avatar','nickname'));
            $li['avatar'] = tomedia($member['avatar']);
            $li['nickname'] = $member['nickname'];
            if($li['paytime'] > 0){
                $li['paytime'] = date('Y-m-d H:i:s',$li['paytime']);
            }
            if($li['finishtime'] > 0){
                $li['finishtime'] = date('Y-m-d H:i:s',$li['finishtime']);
            }
        }


        include wl_template("page/orderList");
    }

    /**
     * Comment: 充值订单导出
     * Author: wlf
     * Date: 2021/11/17 13:16
     */
    public function export($where){
        global $_W,$_GPC;
        $payonlinelist = Util::getNumData('*','wlmerchant_mrecharge_order',$where,'paytime DESC',0,0,1);
        $list = $payonlinelist[0];

        foreach ($list as $key => &$li) {
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('nickname'));
            $li['nickname'] = $member['nickname'];
            if($li['paytime'] > 0){
                $li['paytime'] = date('Y-m-d H:i:s',$li['paytime']);
            }
            if($li['finishtime'] > 0){
                $li['finishtime'] = date('Y-m-d H:i:s',$li['finishtime']);
            }
            $li['orderno'] = "\t".$li['orderno']."\t";
        }

        $filter = array(
            'orderno' => '订单编号',
            'nickname' => '用户昵称',
            'mobile' => '充值号码',
            'money' => '充值金额',
            'channel' => '用户昵称',
            'price' => '支付金额',
            'paytime' => '支付时间',
            'status' => '订单状态',
            'paytype' => '支付方式',
            'finishtime' => '完成时间',
            'reason' => '其他说明'
        );

        $data = array();
        for ($i=0; $i < count($list) ; $i++) {
            foreach ($filter as $key => $title) {
                if($key == 'status') {
                    switch ($list[$i][$key]) {
                        case '1':
                            $data[$i][$key] = '充值中';
                            break;
                        case '2':
                            $data[$i][$key] = '已到账';
                            break;
                        case '3':
                            $data[$i][$key]  = '已退款';
                            break;
                    }
                }else if($key == 'paytype'){
                    switch ($list[$i][$key]) {
                        case '1':
                            $data[$i][$key] = '余额支付';
                            break;
                        case '2':
                            $data[$i][$key] = '微信支付';
                            break;
                        case '3':
                            $data[$i][$key] = '支付宝支付';
                            break;
                        case '4':
                            $data[$i][$key] = '货到付款';
                            break;
                        case '5':
                            $data[$i][$key] = '小程序支付';
                            break;
                        case '6':
                            $data[$i][$key] = '0元购';
                            break;
                        case '7':
                            $data[$i][$key] = '混合支付';
                            break;
                        default:
                            $data[$i][$key]  = '其他或未支付';
                            break;
                    }
                }else if($key == 'channel'){
                    if($list[$i]['channel'] == 1){
                        $data[$i][$key] = '36鲸';
                    }
                    if($list[$i]['type'] == 1){
                        $data[$i][$key] .= '慢充';
                    }else if($list[$i]['type'] == 2){
                        $data[$i][$key] .= '快充';
                    }else if($list[$i]['type'] == 3){
                        $data[$i][$key] .= '特快充';
                    }
                }else {
                    $data[$i][$key] = $list[$i][$key];
                }
            }
        }
        util_csv::export_csv_2($data, $filter, '充值订单表.csv');
        exit();
    }

    /**
     * Comment: 话费充值设置
     * Author: wlf
     * Date: 2021/11/01 17:31
     */
    public function baseSet(){
        global $_W,$_GPC;
        $settings = Setting::wlsetting_read('mobilerecharge');
        if (checksubmit('submit')) {
            $base = $_GPC['set'];
            //数据处理
            $base['account'] = trim($base['account']);
            $base['secretkey'] = trim($base['secretkey']);
            $base['domainname'] = trim($base['domainname']);

            $base['fastjing50price'] = sprintf("%.2f",$base['fastjing50price']);
            $base['fastjing50vip'] = sprintf("%.2f",$base['fastjing50vip']);
            $base['fastjing100price'] = sprintf("%.2f",$base['fastjing100price']);
            $base['fastjing100vip'] = sprintf("%.2f",$base['fastjing100vip']);
            $base['fastjing200price'] = sprintf("%.2f",$base['fastjing200price']);
            $base['fastjing200vip'] = sprintf("%.2f",$base['fastjing200vip']);
            $base['fastjing50one'] = sprintf("%.2f",$base['fastjing50one']);
            $base['fastjing50two'] = sprintf("%.2f",$base['fastjing50two']);
            $base['fastjing100one'] = sprintf("%.2f",$base['fastjing100one']);
            $base['fastjing100two'] = sprintf("%.2f",$base['fastjing100two']);
            $base['fastjing200one'] = sprintf("%.2f",$base['fastjing200one']);
            $base['fastjing200two'] = sprintf("%.2f",$base['fastjing200two']);

            $base['mostjing50price'] = sprintf("%.2f",$base['mostjing50price']);
            $base['mostjing50vip'] = sprintf("%.2f",$base['mostjing50vip']);
            $base['mostjing100price'] = sprintf("%.2f",$base['mostjing100price']);
            $base['mostjing100vip'] = sprintf("%.2f",$base['mostjing100vip']);
            $base['mostjing200price'] = sprintf("%.2f",$base['mostjing200price']);
            $base['mostjing200vip'] = sprintf("%.2f",$base['mostjing200vip']);
            $base['mostjing50one'] = sprintf("%.2f",$base['mostjing50one']);
            $base['mostjing50two'] = sprintf("%.2f",$base['mostjing50two']);
            $base['mostjing100one'] = sprintf("%.2f",$base['mostjing100one']);
            $base['mostjing100two'] = sprintf("%.2f",$base['mostjing100two']);
            $base['mostjing200one'] = sprintf("%.2f",$base['mostjing200one']);
            $base['mostjing200two'] = sprintf("%.2f",$base['mostjing200two']);

            $base['slowjing50price'] = sprintf("%.2f",$base['slowjing50price']);
            $base['slowjing50vip'] = sprintf("%.2f",$base['slowjing50vip']);
            $base['slowjing100price'] = sprintf("%.2f",$base['slowjing100price']);
            $base['slowjing100vip'] = sprintf("%.2f",$base['slowjing100vip']);
            $base['slowjing200price'] = sprintf("%.2f",$base['slowjing200price']);
            $base['slowjing200vip'] = sprintf("%.2f",$base['slowjing200vip']);
            $base['slowjing50one'] = sprintf("%.2f",$base['slowjing50one']);
            $base['slowjing50two'] = sprintf("%.2f",$base['slowjing50two']);
            $base['slowjing100one'] = sprintf("%.2f",$base['slowjing100one']);
            $base['slowjing100two'] = sprintf("%.2f",$base['slowjing100two']);
            $base['slowjing200one'] = sprintf("%.2f",$base['slowjing200one']);
            $base['slowjing200two'] = sprintf("%.2f",$base['slowjing200two']);

            Setting::wlsetting_save($base, 'mobilerecharge');
            wl_message('更新设置成功！', web_url('mobilerecharge/mrecharge/baseSet'));
        }


        include wl_template("page/baseSet");
    }

    /**
     * Comment: 话费充值订单退款
     * Author: wlf
     * Date: 2021/11/16 13:38
     */
    public function refundOrder(){
        global $_W,$_GPC;
        $orderid = $_GPC['id'];
        //删除计划任务
        pdo_delete('wlmerchant_waittask',array('important'=>$orderid,'key' => 11));
        //退款
        $orderno = pdo_getcolumn(PDO_NAME.'mrecharge_order',array('id'=>$orderid),'orderno');
        $res = Mobilerecharge::refund($orderno,'平台管理员退款');
        if($res['status']){
            show_json(1, '退款成功');
        }else{
            show_json(0, '退款失败，请刷新重试');
        }
    }

}
