<?php
defined('IN_IA') or exit('Access Denied');

class TaxipayModuleUniapp extends Uniapp {

    public function pay_bill() {
        global $_W, $_GPC;
        $master = Taxipay::master_get(intval($_GPC['masid']));
        if (empty($master) || $master['status'] != 1) {
            $this->renderError('收款人不存在或被禁用');
        }
        //判断是否是分销商
        $disflag = pdo_getcolumn(PDO_NAME . 'distributor', array('mid' => $master['mid']), 'disflag');
        if (empty($disflag)) {
            $this->renderError('收款人不是分销商，无法向其付款');
        } else if ($disflag == -1) {
            $this->renderError('收款人分销商资格已被禁用，无法向其付款');
        }
		$master['avatar'] = tomedia(pdo_getcolumn(PDO_NAME . 'member', array('id' => $master['mid']), 'avatar'));
		$master['nickname'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $master['mid']), 'nickname');

        $this->renderSuccess('', ['master' => $master, 'urls' => $_W['wlsetting']['taxipay']['urls']]);
    }

    public function pay_order() {
        global $_W, $_GPC;
        $id = intval($_GPC['masid']);
        $money = $_GPC['money'] OR $this->renderError('买单金额不能为空');
        $master = Taxipay::master_get($id);
        if (empty($master)) {
            $this->renderError('师傅信息不存在，请刷新重试');//师傅id
        }

        $disid = pdo_getcolumn(PDO_NAME . 'distributor', array('mid' => $master['mid']), 'id');
        if (empty($disid)) {
            $this->renderError('非分销商无法买单，请刷新重试');
        }

        if ($_W['wlsetting']['taxipay']['is_maxpay'] == 1) {
            $time = strtotime(date('Y-m-d'));
            $moneys = pdo_getcolumn(PDO_NAME . 'order', array('uniacid' => $_W['uniacid'], 'fkid' => $id, 'plugin' => 'taxipay', 'status' => 3 ,'paytime >'=>$time), 'SUM(price)');
            $use_money = $moneys + $money;
            $limit_money = ($master['is_maxpay'] == 1) ? $master['maxpay'] : $_W['wlsetting']['taxipay']['maxpay'];
            if ($limit_money < $use_money) {
                $this->renderError('超出支付金额限制，请明日再试');
            }
        }

        $data = [
            'uniacid'         => $_W['uniacid'],
            'mid'             => $_W['mid'],             //付款人id
            'sid'             => 0,
            'aid'             => $_W['aid'],
            'fkid'            => $id,                    //师傅id
            'plugin'          => 'taxipay',
            'payfor'          => 'taxipayOrder',
            'orderno'         => createUniontid(),
            'status'          => 0,//订单状态：0未支付,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
            'createtime'      => TIMESTAMP,
            'oprice'          => $money,
            'price'           => $money,
            'num'             => 1,
            'vipbuyflag'      => 0,
            'specid'          => $master['mid'],      //师傅的mid
            'goodsprice'      => $money,
            'card_type'       => 0,
            'card_id'         => $disid,              //师傅的分销商id
            'card_fee'        => 0,
            'remark'          => '',
            'spec'            => 0,
            'settlementmoney' => 0
        ];
        pdo_insert(PDO_NAME . 'order', $data);
        $this->renderSuccess('请支付', pdo_insertid());
    }

    public function pay_adv() {
        global $_W, $_GPC;
        $id = intval($_GPC['masid']);
        $master = Taxipay::master_get($id);
        if (empty($master)) {
            $this->renderError('师傅信息不存在，请刷新重试');//师傅id
        }
        $master['disid'] = pdo_getcolumn(PDO_NAME . 'distributor', array('mid' => $master['mid']), 'id');
        if (empty($master['disid'])) {
            $this->renderError('非分销商无法买单，请刷新重试');
        }

        $adv = Taxipay::adv_shows($master['cid']);
        if (!empty($adv)) {
            Taxipay::adv_log($adv, $master);
        }
        $this->renderSuccess('', ['adv' => $adv]);
    }

    /**
     * Comment: 申请成为网约车司机
     * Author: wlf
     * Date: 2020/04/21 17:22
     */
    public function applyDriver(){
        global $_W, $_GPC;
        $data = [];
        $name = trim($_GPC['drivername']);
        $mobile = trim($_GPC['mobile']);
        $plate1 = trim($_GPC['plate1']);
        $plate2 = trim($_GPC['plate2']);
        $plate_number = trim($_GPC['plate_number']);
        $cid = intval($_GPC['cid']);
        if(empty($cid) || empty($_W['mid']) || empty($name) || empty($plate1) || empty($plate_number)  || empty($mobile)){
            $this->renderError('信息录入不全，请刷新重试');
        }
        $data = array(
            'uniacid' => $_W['uniacid'],
            'mid'     => $_W['mid'],
            'cid'     => $cid,
            'plate1'  => $plate1,
            'plate2'  => $plate2,
            'plate_number' => $plate_number,
            'name'    => $name,
            'mobile'  => $mobile,
            'createtime' => time(),
            'status'  => 2
        );
        $res = pdo_insert('wlmerchant_taxipay_master', $data);
        if($res){
            $this->renderSuccess('申请成功');
        }else{
            $this->renderError('申请失败，请刷新重试');
        }
    }

    /**
     * Comment: 获取司机的收款二维码
     * Author: wlf
     * Date: 2020/04/21 18:25
     */
    public function driverQr(){
        global $_W, $_GPC;
        $id = pdo_getcolumn(PDO_NAME.'taxipay_master',array('mid'=>$_W['mid']),'id');
        if(empty($id)){
            $this->renderSuccess('账户错误，请刷新重试');
        }
        $h5qrcode = Taxipay::master_qr_code($id, 'h5');
        $wxappqrcode = Taxipay::master_qr_code($id, 'wxapp');

        $data['h5qr'] = $h5qrcode['img'];
        $data['wxappqr'] = $wxappqrcode['img'];

        $this->renderSuccess('收款二维码',$data);

    }

    /**
     * Comment: 获取所属地区推荐商品
     * Author: wlf
     * Date: 2020/05/08 10:10
     */
    public function tabInfo(){
        global $_W, $_GPC;
        $info = DiyPage::defaultinfo('options');
        $data['tabinfo'] = $info;
        $areaid = pdo_getcolumn(PDO_NAME.'oparea',array('uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']),'areaid');
        $data['tabtitle'] = pdo_getcolumn(PDO_NAME.'area',array('id'=>$areaid),'name');
        $data['tabtitle'] .= '精选';

        $this->renderSuccess('推荐信息',$data);
    }

    /**
     * Comment: 广播通知
     * Author: wlf
     * Date: 2020/05/20 10:33
     */
    public function taxiNotice(){
        global $_W, $_GPC;
        $needorder = pdo_get('wlmerchant_order',array('plugin' => 'taxipay','deliverytype' => 1,'specid' => $_W['mid']),array('id'));
        if(!empty($needorder)){
            pdo_update('wlmerchant_order',array('deliverytype' => 0),array('id' => $needorder['id']));
            $url = $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/taxipay/sys/resource/taxinotice.mp3';
            $this->renderSuccess('语音播报',['status'=>1,'videourl'=> $url]);
        }else{
            $this->renderSuccess('无提示',['status'=>0]);
        }
    }







}