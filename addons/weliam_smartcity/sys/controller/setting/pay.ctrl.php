<?php
defined('IN_IA') or exit('Access Denied');

class Pay_WeliamController{
    /**
     * Comment: 进入设置首页
     * Author: zzw
     * Date: 2019/8/27 15:24
     */
    public function index(){
        global $_W,$_GPC;
        $name = $_GPC['name'] ? : '';
        #1、获取已保存的设置信息
        $setInfo = Setting::wlsetting_read('payment_set');
        #2、保存设置信息
        if($name){
            $set = $_GPC['set'] ? : [];
            $setInfo[$name] = $set;

            Setting::wlsetting_save($setInfo,'payment_set');
        }
        #3、判断支付方式是否存在 不存在则变更为未开启
        if(is_array($setInfo) && count($setInfo) > 0){
            foreach($setInfo as $key => &$val){
                //判断微信支付方式是否存在
                $weChat = pdo_getcolumn(PDO_NAME."payment",['id'=>$val['wechat']],'id');
                $val['wechat'] = $weChat > 0 ? $weChat : -1;
                //判断支付宝支付方式是否存在
                $aliPay = pdo_getcolumn(PDO_NAME."payment",['id'=>$val['alipay']],'id');
                $val['alipay'] = $aliPay > 0 ? $aliPay : -1;
            }
        }


        include wl_template('setting/pay_set');
    }
    /**
     * Comment: 支付方式列表
     * Author: zzw
     * Date: 2019/8/27 15:25
     */
    public function infoList(){
        global $_GPC,$_W;
        #1、参数获取
        $name = $_GPC['name'] ? : '';
        #2、获取已保存的设置信息
        $setInfo = Setting::wlsetting_read('payment_set');
        $set = $setInfo[$name];
        #3、获取微信支付方式列表
        $weChat = pdo_getall(PDO_NAME."payment",['uniacid'=>$_W['uniacid'],'type'=>1],['id','name']);
        #4、获取支付宝支付方式列表
        $aliPay = pdo_getall(PDO_NAME."payment",['uniacid'=>$_W['uniacid'],'type'=>2],['id','name']);
        //云收单支付方式
        if(Customized::init('yunmis160') > 0){
            $yunPay = pdo_getall(PDO_NAME."payment",['uniacid'=>$_W['uniacid'],'type'=>3],['id','name']);
        }
        #5、定义模块信息
        $plugin = Payment::getBalanceModel();
        //881定制内容
        $isAuth = Customized::init('diy_userInfo');

        include wl_template('setting/pay_list');
    }
    /**
     * Comment: 进入支付管理页面
     * Author: zzw
     * Date: 2019/8/27 15:26
     */
    public function administration(){
        global $_W,$_GPC;
        #1、获取支付设置信息
        $setInfo = Setting::wlsetting_read('payment_set');
        $data = [];
        if(is_array($setInfo) && count($setInfo) > 0){
            foreach ($setInfo as $index => $item){
                $data = array_merge($data,array_values($item));
            }
        }
        //设置商户设置项
        $wxstorepaysetids = pdo_fetchall('select distinct wxallid from ' . tablename(PDO_NAME.'merchantdata')."WHERE uniacid = {$_W['uniacid']}");
        if(is_array($wxstorepaysetids) && count($wxstorepaysetids) > 0){
            foreach ($wxstorepaysetids as $index => $swx){
                $data = array_merge($data,array_values($swx));
            }
        }
        $appstorepaysetids = pdo_fetchall('select distinct appallid from ' . tablename(PDO_NAME.'merchantdata')."WHERE uniacid = {$_W['uniacid']}");
        if(is_array($appstorepaysetids) && count($appstorepaysetids) > 0){
            foreach ($appstorepaysetids as $index => $sapp){
                $data = array_merge($data,array_values($sapp));
            }
        }
        //设置代理设置项
        $wxagentpaysetids = pdo_fetchall('select distinct wxpaysetid from ' . tablename(PDO_NAME.'agentusers')."WHERE uniacid = {$_W['uniacid']}");
        if(is_array($wxagentpaysetids) && count($wxagentpaysetids) > 0){
            foreach ($wxagentpaysetids as $index => $awx){
                $data = array_merge($data,array_values($awx));
            }
        }
        $appagentpaysetids = pdo_fetchall('select distinct apppaysetid from ' . tablename(PDO_NAME.'agentusers')."WHERE uniacid = {$_W['uniacid']}");
        if(is_array($appagentpaysetids) && count($appagentpaysetids) > 0){
            foreach ($appagentpaysetids as $index => $aapp){
                $data = array_merge($data,array_values($aapp));
            }
        }

        #1、获取支付列表
        $list = pdo_fetchall("SELECT id,name,type,status,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%S') as create_time FROM ".tablename(PDO_NAME."payment")
                             ." WHERE uniacid = {$_W['uniacid']} ORDER BY create_time DESC ");
        include wl_template('setting/pay_admin');
    }
    /**
     * Comment: 编辑支付方式
     * Author: zzw
     * Date: 2019/8/28 9:49
     */
    public function editInfo(){
        global $_W,$_GPC;
        $id = $_GPC['id'] ? : '';
        if($_W['ispost']){
            #1、参数接收
            $info = $_GPC['info'] ? : [];
            $data['uniacid'] = $_W['uniacid'];
            $data['name'] = $info['name'];//名称
            $data['type'] = $info['type'];//支付类型1微信支付2支付宝支付
            $data['param'] = json_encode($info);//支付参数
            #2、修改/添加操作
            if($id){
                //id  存在，修改操作
                $res = pdo_update(PDO_NAME."payment",$data,['id'=>$id]);
            }else{
                //id  不存在，添加操作
                $data['create_time'] = time();
                $res = pdo_insert(PDO_NAME."payment",$data);
            }
            #2、判断是否成功
            if($res) wl_json(0,'操作成功');
                else wl_json(1,'操作失败');
        }

        include wl_template('setting/pay_edit');
    }
    /**
     * Comment: 获取某条支付方式的配置详情
     * Author: zzw
     * Date: 2019/8/28 15:03
     */
    public function getInfo(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] ? : 0;
        #2、信息获取
        $info = pdo_get(PDO_NAME."payment",['id'=>$id]);
        $params = json_decode($info['param'],true);

        wl_json(0,'信息内容',$params);
    }
    /**
     * Comment: 删除支付方式
     * Author: zzw
     * Date: 2019/8/28 14:53
     */
    public function delete(){
        global $_W,$_GPC;
        $id = $_GPC['id'] ? : '';
        $res = pdo_delete(PDO_NAME."payment",['id'=>$id]);
        if($res) show_json(1,'成功');
            else show_json(0,'删除失败，请重试!');
    }
    /**
     * Comment: 上传证书
     * Author: zzw
     * Date: 2019/8/28 9:50
     */
    public function uploadCertificate(){
        global $_W,$_GPC;
        UploadFile::uploadIndex($_FILES,1,0);
    }
}
