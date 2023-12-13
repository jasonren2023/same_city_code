<?php
defined('IN_IA') or exit('Access Denied');

class Active_WeliamController {

    /**
     * Comment: 同城配送商品列表
     * Author: wlf
     * Date: 2020/03/19 14:52
     */
    public function activelist() {
        global $_W, $_GPC;
        $page = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $where = ['aid'=>$_W['aid'],'uniacid'=>$_W['uniacid']];
        $where['status'] = $status = !empty($_GPC['status']) ? intval($_GPC['status']) : 2;
        if (is_store()) {
            $where['sid'] = $_W['storeid'];
        }

        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $where['@name@'] = $_GPC['keyword'];
                        break;
                    case 2:
                        $where['@id@'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
                if ($_GPC['keywordtype'] == 3) {
                    $keyword = $_GPC['keyword'];
                    $params[':storename'] = "%{$keyword}%";
                    $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND storename LIKE :storename", $params);
                    if ($merchants) {
                        $sids = "(";
                        foreach ($merchants as $key => $v) {
                            if ($key == 0) {
                                $sids .= $v['id'];
                            } else {
                                $sids .= "," . $v['id'];
                            }
                        }
                        $sids .= ")";
                        $where['sid#'] = $sids;
                    } else {
                        $where['sid#'] = "(0)";
                    }
                }
            }
        }

        //导出
        if($_GPC['export']){
            $this -> export($where);
        }

        $list = Util::getNumData('*', PDO_NAME . 'delivery_activity', $where, 'sort DESC,id DESC', $page, $pageIndex, 1);
        $pager = $list[1];
        $list = $list[0];
        foreach ($list as &$li){
            $li['storename'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$li['sid']),'storename');
            $li['catename'] = pdo_getcolumn(PDO_NAME.'delivery_category',array('id'=>$li['cateid']),'name');
            $li['placeorder'] = pdo_getcolumn('wlmerchant_delivery_order',array('gid' => $li['id'],'status >' => 0),array("SUM(num)"));
            $li['placeorder'] = $li['placeorder']?$li['placeorder']:0;
            //剩余数量
            if($li['allstock'] > 0){
                $li['surplus'] = sprintf("%.0f",$li['allstock'] - $li['placeorder']);
            }
        }
        if (is_store()) {
            $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 2 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status7 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 7 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 4 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 5 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 6 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 8 and aid={$_W['aid']} and sid = {$_W['storeid']}");
        }else{
            $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 2 and aid={$_W['aid']}");
            $status7 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 7 and aid={$_W['aid']}");
            $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 4 and aid={$_W['aid']}");
            $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 5 and aid={$_W['aid']}");
            $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 6 and aid={$_W['aid']}");
            $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'delivery_activity') . " WHERE uniacid={$_W['uniacid']} and status = 8 and aid={$_W['aid']}");
        }

        include wl_template('delivery/activelist');
    }


    /**
     * Comment: 配送商品导出
     * Author: wlf
     * Date: 2022/06/13 16:42
     */
    public function export($where){
        global $_W, $_GPC;
        $list = Util::getNumData('*', PDO_NAME . 'delivery_activity', $where, 'sort DESC,id DESC', 0, 0, 1);
        $list = $list[0];
        foreach ($list as &$st){
            $st['storename'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$st['sid']),'storename');
            $st['catename'] = pdo_getcolumn(PDO_NAME.'delivery_category',array('id'=>$st['cateid']),'name');
            if(!empty($st['thumbs'])){
                $st['thumbs'] = unserialize($st['thumbs']);
                $st['thumbs'] = implode(",",$st['thumbs']);
            }
        }

        $filter = [
            'name' => '商品名',
            'storename' => '商家名',
            'catename' => '分类',
            'thumb' => '缩略图',
            'thumbs' => '图集',
            'price' => '价格',
            'oldprice' => '原价',
            'deliveryprice' => '额外配送费',
            'vipstatus' => '会员特权（0无1减免2特供）',
            'vipdiscount' => '会员减免',
            'status' => '商品状态（2上架4下架）',
            'sort' => '排序',
        ];

        $data = array();
        for ($i=0; $i < count($list) ; $i++) {
            foreach ($filter as $key => $title) {
                $data[$i][$key] = $list[$i][$key];
            }
        }

        util_csv::export_csv_2($data, $filter, '配送商品表.csv');
        exit();

    }

    /**
     * Comment: 同城配送商品创建
     * Author: wlf
     * Date: 2020/03/19 16:49
     */
    public function createactive(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if ($_W['ispost']) {
            $goods = $_GPC['goods'];
            if (empty($goods['sid'])) {
                if (is_store()) {
                    $goods['sid'] = $_W['storeid'];
                    $store = pdo_get('wlmerchant_merchantdata',array('id' => $_W['storeid']),array('aid','audits','storename'));
                    if($goods['status'] == 2 && empty($store['audits'])){
                        $goods['status'] = 5;
                    }
                }
                else {
                    wl_message('请选择商户');
                }
            }
            if($goods['creditmoney'] > $goods['price']){
                wl_message('积分可抵扣金额不能大于商品售价');
            }
            empty($goods['name']) && wl_message('请填写商品名称');
            empty($goods['cateid']) && wl_message('请选择商品分类');
            $goods['detail']        = htmlspecialchars_decode($goods['detail']);
            $goods['thumbs']        = serialize($goods['thumbs']);

            //分销商分佣数组
            if($goods['isdistri']){
                $disarray = [];
                $disleid = $_GPC['disleid'];
                $onedismoney = $_GPC['onedismoney'];
                $twodismoney = $_GPC['twodismoney'];
                foreach($disleid as $dkey => $dle){
                    $dlea['onedismoney'] = sprintf("%.2f",$onedismoney[$dkey]);
                    $dlea['twodismoney'] = sprintf("%.2f",$twodismoney[$dkey]);
                    $disarray[$dle] = $dlea;
                }
                $goods['disarray'] = serialize($disarray);
            }

            //团长分红数组
            if($goods['disgroup'] > 0){
                $grouparray = [];
                $groupleid = $_GPC['groupleid'];
                $onegroupmoney = $_GPC['onegroupmoney'];
                $twogroupmoney = $_GPC['twogroupmoney'];
                foreach($groupleid as $gkey => $gle){
                    $glea['onegroupmoney'] = sprintf("%.2f",$onegroupmoney[$gkey]);
                    $glea['twogroupmoney'] = sprintf("%.2f",$twogroupmoney[$gkey]);
                    $grouparray[$gle] = $glea;
                }
                $goods['grouparray'] = serialize($grouparray);
            }

            if($id){
                $goodsid = $id;
                $res1 = pdo_update('wlmerchant_delivery_activity',$goods,array('id' => $id));
            }else{
                $goods['uniacid'] = $_W['uniacid'];
                $goods['aid'] = $_W['aid'];
                $goods['createtime'] = time();
                $res1 = pdo_insert(PDO_NAME . 'delivery_activity',$goods);
                $goodsid = pdo_insertid();
            }
            //获取规格
            if(!empty($goods['optionstatus'])){
                $specname = $_GPC['specname'];
                $specprice = $_GPC['specprice'];
                $specoldprice = $_GPC['specoldprice'];
                $specallstock = $_GPC['specallstock'];
                $specdaystock = $_GPC['specdaystock'];
                $specids = $_GPC['specids'];
                if(empty($specname)){
                    wl_message('请添加规格项或关闭多规格设置');
                }
                foreach ($specname as $key => $name){
                    $spec = array(
                        'name'    => $specname[$key],
                        'price'   => $specprice[$key],
                        'oldprice'=> $specoldprice[$key],
                        'allstock'=> $specallstock[$key],
                        'daystock'=> $specdaystock[$key],
                    );
                    if(empty($specids[$key])){
                        $spec['uniacid'] = $_W['uniacid'];
                        $spec['goodsid'] = $goodsid;
                        $res2 = pdo_insert(PDO_NAME . 'delivery_spec',$spec);
                        $specid[] = pdo_insertid();
                    }else{
                        $specid[] = $specids[$key];
                        $res2 = pdo_update('wlmerchant_delivery_spec',$spec,array('id' => $specids[$key]));
                    }
                }
                pdo_query('delete from ' . tablename('wlmerchant_delivery_spec') . ' where goodsid = '.$goodsid.' AND id not in ('.implode(',' , $specid).')');
            }
            if($res1 || $res2){

                if($goods['status'] == 5){
                    $first   = '您好，您有一个待审核任务需要处理';
                    $type    = '审核商品';
                    $content = '配送商品:' . $goods['name'];
                    $status  = '待审核';
                    $remark  = "商户[" . $store['storename'] . "]上传了一个同城配送商品待审核,请管理员尽快前往后台审核";
                    News::noticeAgent('storegood' , $_W['aid'] , $first , $type , $content , $status , $remark , time() , '');
                }

                wl_message('保存成功！',web_url('citydelivery/active/activelist',array('page'=>$_GPC['page'])),'success');
            }else{
                wl_message('保存失败或无内容修改！' ,'','errno');
            }
        }
        if($id){
            $goods = pdo_get('wlmerchant_delivery_activity',array('id' => $id));
            if($goods['optionstatus']){
                $specs = pdo_getall('wlmerchant_delivery_spec',array('uniacid' => $_W['uniacid'],'goodsid' =>$id));
            }
            $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $goods['sid']),array('id','logo','storename'));
            $goods['thumbs'] = unserialize($goods['thumbs']);
            $cate = pdo_getall('wlmerchant_delivery_category',array('sid' => $goods['sid'],'status' => 1),array('id','name'));
            if($goods['isdistri']){
                $disarray = unserialize($goods['disarray']);
            }
            if(!empty($goods['grouparray'])){
                $grouparray = unserialize($goods['grouparray']);
            }

        }
        if(is_store()){
            $cate = pdo_getall('wlmerchant_delivery_category',array('sid' => $_W['storeid'],'status' => 1),array('id','name'));
        }

        if($_W['wlsetting']['distribution']['switch']){
            $dislevel = pdo_getall('wlmerchant_dislevel', array('uniacid' => $_W['uniacid']),['id','name']);
            $grouplevel = pdo_getall('wlmerchant_grouplevel', array('uniacid' => $_W['uniacid']),['id','name']);
        }

        include wl_template('delivery/createactive');
    }

    /**
     * Comment: 同城配送商品规格页面
     * Author: wlf
     * Date: 2020/03/20 14:52
     */
    public function specpage(){
        include wl_template('delivery/specpage');
    }
    /**
     * Comment: 同城配送商品分类列表异步获取
     * Author: wlf
     * Date: 2020/03/20 16:23
     */
    function cateinfo(){
        global $_W, $_GPC;
        $sid = $_GPC['sid'];
        $seconds = pdo_getall('wlmerchant_delivery_category', array('uniacid' => $_W['uniacid'], 'sid' => $sid),array('id','name'));
        die(json_encode(array('errno' => 0, 'twotype' => $seconds)));
    }

    /**
     * Comment: 同城配送商品列表修改人气和排序
     * Author: wlf
     * Date: 2020/03/25 14:23
     */
    function changepv(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $newvalue = trim($_GPC['value']);
        if ($type == 1) {
            $res = pdo_update('wlmerchant_delivery_activity', array('pv' => $newvalue), array('id' => $id));
        } elseif ($type == 2) {
            $res = pdo_update('wlmerchant_delivery_activity', array('sort' => $newvalue), array('id' => $id));
        }
        if ($res) {
            show_json(1, '修改成功');
        } else {
            show_json(0, '修改失败，请重试');
        }
    }
    /**
     * Comment: 同城配送基础设置
     * Author: wlf
     * Date: 2020/04/27 09:38
     */
    function base(){
        global $_W, $_GPC;
        $apiset = Setting::wlsetting_read('api');
        if (checksubmit('submit')) {
            $base = $_GPC['base'];
            $base['make'] = $_GPC['make'];
            $base['dada'] = $_GPC['dada'];
            $base['UUpt'] = $_GPC['UUpt'];
            
            Setting::agentsetting_save($base, 'citydelivery');
            wl_message('保存设置成功！', referer(), 'success');
        }
        $base = Setting::agentsetting_read('citydelivery');
        $make = $base['make'];
        $dada = $base['dada'];
        $UUpt = $base['UUpt'];
        //获取社群
        $communitylist = pdo_getall('wlmerchant_community', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array('id', 'communname'));

        include wl_template('delivery/baseset');
    }

    /**
     * Comment: 修改商品状态
     * Author: wlf
     * Date: 2020/05/11 18:37
     */
    function changeStatus(){
        global $_W,$_GPC;
        $storeid = $_GPC['storeid']; //商家id
        $goodsid = $_GPC['goodsid']; //商品id
        $status = trim($_GPC['status']) ? trim($_GPC['status']) : 2;  //状态 2上架 4下架 8放入回收站
        if(empty($goodsid)){
            $this->renderError('无商品信息，请刷新重试');
        }
        if(is_store()){
            $store = pdo_get('wlmerchant_merchantdata',array('id' => $storeid),array('aid','storename','audits'));
            if($status == 2 && empty($store['audits'])){
                $status = 5;
            }
        }
        $res = pdo_update('wlmerchant_delivery_activity',array('status' => $status),array('id' => $goodsid));
        if($res){
            if($status == 5){
                $goodname =  pdo_getcolumn(PDO_NAME.'delivery_activity',array('id'=>$goodsid),'name');
                $first   = '您好，您有一个待审核任务需要处理';
                $type    = '审核商品';
                $content = '配送商品:' . $goodname;
                $status  = '待审核';
                $remark  = "商户[" . $store['storename'] . "]上传了一个同城配送商品待审核,请管理员尽快前往后台审核";
                News::noticeAgent('storegood' , $_W['aid'] , $first , $type , $content , $status , $remark , time() , '');
            }
            show_json(1, '操作成功');
        }else{
            show_json(0, '操作失败，请重试');
        }
    }

    /**
     * Comment: 审核商品
     * Author: wlf
     * Date: 2021/10/27 16:47
     */
    function passStatus(){
        global $_W,$_GPC;
        $goodsid = $_GPC['goodsid']; //商品id
        $status = trim($_GPC['status']) ? trim($_GPC['status']) : 2;  //状态 2上架 4下架 8放入回收站
        $remark = trim($_GPC['remark']);
        $res = pdo_update('wlmerchant_delivery_activity',array('status' => $status),array('id' => $goodsid));

        if ($status == 2) {
            News::goodsToExamine($goodsid,'citydelivery');
        } else {
            News::goodsToExamine($goodsid,'citydelivery','未通过',$remark);
        }
        if ($res) {
            show_json(1, '操作成功');
        } else {
            show_json(0, '操作失败，请重试');
        }
    }



    /**
     * Comment: 彻底删除商品
     * Author: wlf
     * Date: 2020/05/11 18:50
     */
    function deleteGoods(){
        global $_W,$_GPC;
        $goodsid = $_GPC['goodsid']; //商品id
        $res = pdo_delete('wlmerchant_delivery_activity',array('id'=>$goodsid));
        pdo_delete('wlmerchant_delivery_spec',array('goodsid'=>$goodsid));
        if($res){
            show_json(1, '操作成功');
        }else{
            show_json(0, '操作失败，请重试');
        }
    }

    /**
     * Comment: 导入商品页面
     * Author: wlf
     * Date: 2020/05/13 09:46
     */
    function importactive(){
        global $_W,$_GPC;

        include wl_template('delivery/importactive');
    }
    /**
     * Comment: 导入商品操作
     * Author: wlf
     * Date: 2020/05/13 11:57
     */
    function csv_add(){
        global $_W;
        //1.
        $filename = $_FILES['csv_file']['name'];
        $filename = substr($filename, -4, 4);
        if (empty ($filename)) {
            wl_message("请选择要导入的CSV文件", web_url('citydelivery/active/importactive'), 'success');
            exit;
        }
        if ($filename !== '.csv') {
            wl_message("请选择CSV文件", web_url('citydelivery/active/importactive'), 'success');
            exit;
        }
        $file_path = $_FILES['csv_file']['tmp_name'];
        $file_size = filesize($file_path);    //文件大小
        if ($file_size == 0) {
            wl_message("没有任何数据", web_url('citydelivery/active/importactive'), 'success');
            exit;
        }
        $info = util_csv::read_csv_lines($file_path, 999, 0);
        foreach ($info as $k => &$v) {
            //3-1 判断是否存在数据 不存在是空行，不进行任何操作
            if (!is_array($v)) {
                unset($info[$k]);
                continue;
            }
            //3-2 编码转换  由gbk转为urf-8
            $separator = '*separator*';//分割符 写成长字符串 防止出错
            $encodres = mb_detect_encoding(implode($separator, $v), array("ASCII","GB2312","GBK","UTF-8"));
            if($encodres != 'UTF-8'){
                $v = explode($separator, iconv($encodres, 'utf-8', implode($separator, $v)));
            }
            //处理数据
            $store = pdo_get(PDO_NAME.'merchantdata',array('uniacid'=>$_W['uniacid'],'storename'=>trim($v[1])),array('id','aid'));
            $v[1] = $store['id'];
            if(empty($store['id'])){
                $v['send_result'] = '导入失败，无所填商户';
                continue;
            }
            $cateid = pdo_getcolumn(PDO_NAME.'delivery_category',array('uniacid'=>$_W['uniacid'],'name'=>trim($v[2]),'sid'=> trim($v[1])),'id');
            if(empty($cateid)){
                $v['send_result'] = '导入失败，无所填分类';
                continue;
            }
            if(!empty($v[4])){
                $thumbs = serialize(explode(',' , trim($v['4'])));
            }else{
                $thumbs = '';
            }
            if($v[10] != 2){$v[10] = 4;}
            if($v[8] != 1 && $v[8] != 2){$v[8] = 0;}
            $data = [
                'uniacid'  => $_W['uniacid'],
                'aid'      => $store['aid'],
                'sid'      => $store['id'],
                'name'     => $v[0],
                'price'    => $v[5],
                'oldprice' => $v[6],
                'status'   => $v[10],
                'thumb'    => $v[3],
                'thumbs'   => $thumbs,
                'cateid'   => $cateid,
                'createtime' => time(),
                'vipstatus' => $v[8],
                'vipdiscount' => $v[9],
                'deliveryprice'=> $v[7],
                'sort'      => $v[11]
            ];
            $res = pdo_insert(PDO_NAME.'delivery_activity',$data);
            if($res){
                $v['send_result'] = '导入成功';
            }else{
                $v['send_result'] = '导入失败，请重试';
            }
        }
        //结果导出
        $filter = array(
            0 => '商品名',
            1 => '所属商户',
            2 => '所在分类',
            3 => '缩略图',
            4 => '图集',
            5 => '价格',
            6 => '原价',
            7 => '额外配送费',
            8 => '特权类型',
            9 => '会员减免',
            10 => '商品状态',
            11 => '商品排序',
            'send_result' => '发货结果',
        );
        #5、返回批量发货的结果信息表
        util_csv::export_csv_2($info, $filter, '商品导入结果信息.csv');
    }


    /**
     * Comment: 批量修改商品信息
     * Author: wlf
     * Date: 2020/06/01 14:38
     */
    public function allchangestatus(){
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $type = $_GPC['type'];
        foreach ($ids as $v){
            $good = pdo_get('wlmerchant_delivery_activity',array('id' => $v),array('sid','status'));
            if($type == 2){
                $status = 0;
                if(is_store()){
                    $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$good['sid']),'audits');
                    if(empty($examine)){
                        $status = 5;
                    }
                }
                if(empty($status)){
                    $status = 2;
                }
                pdo_update('wlmerchant_delivery_activity', array('status' => $status), array('id' => $v));
            }else if($type == 8 && $good['status'] == 8){
                pdo_delete('wlmerchant_delivery_activity',array('id'=>$v));
                pdo_delete('wlmerchant_delivery_spec',array('goodsid'=>$v));
            }else{
                pdo_update('wlmerchant_delivery_activity', array('status' => $type), array('id' => $v));
            }
        }
        show_json(1, '操作成功');
    }

    /**
     * Comment: 复制商品分类
     * Author: wlf
     * Date: 2022/03/28 11:04
     */
    public function copycate(){
        global $_W, $_GPC;

        $stores = pdo_getall('wlmerchant_merchantdata',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'enabled' => 1,'deliverystatus' => 1),array('id','storename'));
        if ($_W['ispost']) {
            $expressid = $_GPC['expressid'];
            $passiveid = $_GPC['passiveid'];
            $copystatus = $_GPC['copystatus'];
            if (in_array($expressid, $passiveid)) {
                wl_message('目标商户不能包含模板商户', 'referer', 'error');
            }
            //循环插入数据
            if(empty($passiveid)){
                $passiveid = pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_merchantdata')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND id != {$expressid} AND enabled = 1 AND deliverystatus = 1 ORDER BY id DESC");
                foreach ($passiveid as $item) {
                    $this->insert_cate($item['id'], $expressid, $copystatus);
                }
            }else{
                foreach ($passiveid as $item) {
                    $this->insert_cate($item, $expressid, $copystatus);
                }
            }
            wl_message('同步完成，请检查是否同步正确', 'referer', 'success');
        }

        include wl_template('delivery/copycate');
    }

    /**
     * Comment: 复制商品分类操作
     * Author: wlf
     * Date: 2022/03/28 11:43
     */
    private function insert_cate($passiveid, $expressid, $copystatus) {
        global $_W, $_GPC;
        if ($copystatus) {
            pdo_delete('wlmerchant_delivery_category', array('sid' => $passiveid, 'uniacid' => $_W['uniacid']));
        }
        $cates = pdo_getall('wlmerchant_delivery_category',array('sid' => $expressid),array('uniacid','name','aid','sort','status'));
        if(empty($cates)){
            wl_message('模板商户无可用分类', 'referer', 'error');
        }else{
            foreach ($cates as $ca){
                $ca['sid'] = $passiveid;
                pdo_insert(PDO_NAME.'delivery_category',$ca);
            }
        }
    }


}