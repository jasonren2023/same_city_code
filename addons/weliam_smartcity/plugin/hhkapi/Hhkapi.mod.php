<?php
defined('IN_IA') or exit('Access Denied');

class Hhkapi{
    /**
     * Comment: 定制功能核心接口信息
     * Author: wlf
     * Date: 2022/04/15 19:46
     * @param $id
     * @return bool|mixed
     */

    public static function coreApi($mid,$sid){
        global $_W,$_GPC;
        $base = Setting::agentsetting_read('hhkapi');
        //判断权限
        $aut = Store::checkAuthority('hhkapi',$sid);
        if(empty($aut)){
            //新会员成为会员
            $flag = pdo_get('wlmerchant_halfcardmember',array('mid' => $mid,'uniacid' => $_W['uniacid']),array('id'));
            if(empty($flag)){
                //添加成为会员
                $viptype = pdo_get('wlmerchant_halfcard_type',array('id' => $base['viptypeid']));
                $member = pdo_get(PDO_NAME.'member',array('id'=>$mid),['nickname','mobile']);
                $expiretime = time() + $viptype['days']*86400;
                $vipinfo = [
                    'uniacid' => $_W['uniacid'],
                    'aid'     => $_W['aid'],
                    'mid'     => $mid,
                    'expiretime' => $expiretime,
                    'createtime' => time(),
                    'username'   => $member['nickname'],
                    'levelid'  => $viptype['levelid'],
                    'channel'  => 4
                ];
                pdo_insert(PDO_NAME . 'halfcardmember', $vipinfo);
                //同步到惠花卡
                if (file_exists(PATH_MODULE . 'lsh.log')) {
                    Halfcard::toHccardMode($mid,$member['nickname'],$member['mobile'],$viptype['levelid']);
                }
            }
            //和店长绑定上下级
            $merchantuserid = pdo_getcolumn(PDO_NAME.'merchantuser',array('storeid'=>$sid,'ismain'=>1),'mid');
            Distribution::addJunior($merchantuserid,$mid);
        }
    }


}