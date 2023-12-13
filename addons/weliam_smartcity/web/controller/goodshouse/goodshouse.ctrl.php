<?php
defined('IN_IA') or exit('Access Denied');

class Goodshouse_WeliamController
{
    /**
     * 编辑商品
     */
    public function createactive()
    {
        global $_W , $_GPC;
        $id     = intval($_GPC['id']);
        $plugin = trim($_GPC['plugin']) ? trim($_GPC['plugin']) : 'rush';

        if ($_W['ispost']) {
            $goods = $_GPC['goods'];
            if (empty($goods['sid'])) {
                if (is_store()) {
                    $goods['sid'] = $_W['storeid'];
                }
            }
            if( empty($goods['pftid'])){
                if(empty($goods['sid'])){
                    wl_message('商户错误,请选择商户');
                }
                $goods['aid'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$goods['sid']),'aid');
            }
            empty($goods['name']) && wl_message('请填写商品名称');
            empty($goods['num']) && wl_message('请填写商品数量');
            if($goods['creditmoney'] > $goods['price'] && $goods['creditmoney'] > 0) wl_message('积分抵扣金额不能大于商品金额');
            //判断开启多规格时  是否添加了多规格信息
            if ($_GPC['optionstatus'] == 1 && count($_GPC['spec_id']) <= 0) wl_message('请设置多规格信息');
            $goods['cutoffstatus']  = $_GPC['cutoffstatus'];
            $goods['optionstatus']  = $_GPC['optionstatus'];
            $goods['independent']   = $_GPC['independent'];
            $goods['isdistri']      = $_GPC['isdistri'];
            $goods['isdistristatus']= $_GPC['isdistristatus'];
            $goods['vipstatus']     = $_GPC['vipstatus'];
            $goods['detail']        = base64_encode(htmlspecialchars_decode($goods['detail']));
            $goods['describe']      = base64_encode(htmlspecialchars_decode($goods['describe']));
            $goods['thumbs']        = serialize($goods['thumbs']);
            $goods['extension_img'] = serialize($goods['extension_img']);
            $tag                    = $_GPC['tag'];
            $goods['tag']           = serialize($tag);
            $userlabel              = $_GPC['userlabel'];
            $goods['userlabel']     = serialize($userlabel);
            $level                  = $_GPC['level'];
            $goods['level']         = serialize($level);
            $goods['name']          = htmlspecialchars_decode($goods['name']);
            $time                       = $_GPC['time'];
            $goods['starttime']         = strtotime($time['start']);
            $goods['endtime']           = strtotime($time['end']);
            $goods['cutofftime']        = strtotime($_GPC['cutofftime']);
            $goods['cateid']            = intval($_GPC['cateid']);
            $goods['pay_type']          = serialize($_GPC['pay_type']);
            $goods['cash_back']         = $_GPC['cash_back'] ? : 0;
            $goods['return_proportion'] = $_GPC['return_proportion'] ? : 0;
            $goods['yuecashback'] = sprintf("%.2f",$goods['yuecashback']);
            $goods['vipyuecashback'] = sprintf("%.2f",$goods['vipyuecashback']);

            if ($goods['cash_back'] == 1 && $goods['return_proportion'] < 1) wl_message('请填写返现比例');
            if(!$id){
                $goods['aid'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$goods['sid']),'aid');
            }
            //抢购状态通过抢购时间判断
            if (is_store()) {
                $audits = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $_W['storeid']] , 'audits');
                if (empty($audits)) {
                    $goods['status'] = 5;
                }
            }
            if ($goods['status'] != 5) {
                if(!empty($_GPC['tostatus'])){
                    if ($goods['starttime'] > time()) {
                        $goods['status'] = 1;
                    }
                    else if ($goods['starttime'] < time() && time() < $goods['endtime']) {
                        $goods['status'] = 2;
                    }
                    else if ($goods['endtime'] < time()) {
                        $goods['status'] = 3;
                    }
                }else{
                    $goods['status'] = 4;
                }
            }
            //使用类型
            if($goods['usestatus'] == 3){
                $goods['optionstatus'] = 0;
                $goods['appointstatus'] = 0;
                $goods['allowapplyre'] = 1;
                $goods['overrefund'] = 0;
            }

            //定时购买
            if ($goods['usedatestatus'] == 1) {
                $goods['week'] = serialize($goods['week']);
                $goods['day'] = '';
            }else if ($goods['usedatestatus'] == 2) {
                $goods['day'] = serialize($goods['day']);
                $goods['week'] = '';
            }else{
                $goods['usedatestatus'] = 0;
                $goods['week'] = '';
                $goods['day'] = '';
            }
            //会员减免
            if($goods['vipstatus'] == 1){
                $viparray = [];
                $vipleid = $_GPC['vipleid'];
                $vipprice = $_GPC['vipprice'];
                $storeset = $_GPC['storeset'];
                foreach($vipleid as $key => $vle){
                    $vipa['vipprice'] = sprintf("%.2f",$vipprice[$key]);
                    if(is_store()){
                        $vipa['storeset'] = $vipa['vipprice'];
                    }else{
                        $vipa['storeset'] = sprintf("%.2f",$storeset[$key]);
                    }
                    $viparray[$vle] = $vipa;
                }
                $goods['viparray'] = serialize($viparray);
            }
            //分销商分佣数组
            if(empty($goods['isdistri'])){
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
            //预约数组
            if($goods['appointstatus']>0){
                $appointarray = [];
                $appstartTime = $_GPC['appstartTime'];
                $appendTime = $_GPC['appendTime'];
                $peoplenums = $_GPC['peoplenums'];
                if(!is_array($appstartTime)){
                    wl_message('请设置预约时间段信息!');
                }
                foreach($appstartTime as $appkey => $appoint){
                    $appinta['startTime'] = $appstartTime[$appkey];
                    $appinta['endTime'] = $appendTime[$appkey];
                    $appinta['peoplenums'] = $peoplenums[$appkey];
                    $appointarray[] = $appinta;
                }
                $goods['appointarray'] = serialize($appointarray);
            }

            if ($plugin == 'rush') {
                //商品阶梯价操作
                $goods['lp_status'] = $_GPC['lp_status'] ? : 0;
                $goods['lp_set']    = is_array($_GPC['ld']) ? serialize(array_values($_GPC['ld'])) : '';
                if ($goods['lp_status'] == 1 && is_array($_GPC['ld'])) {
                    $lpSet  = array_values($_GPC['ld']);
                    $maxMax = max(array_column($lpSet , 'max'));
                    if ($maxMax > $goods['num']) {
                        wl_message('阶梯价上限不可超过最大库存!');
                    }
                }

                if (!empty($id)) {
                    Rush::updateActive($goods , ['id' => $id]);
                }
                else {
                    $id = Rush::saveRushActive($goods);
                }
            }
            else if ($plugin == 'groupon') {

                if (!empty($id)) {
                    Groupon::updateActive($goods , ['id' => $id]);
                }
                else {
                    $id = Groupon::savegrouponActive($goods);
                }
            }
            else if ($plugin == 'fightgroup') {
                if($goods['peoplenum'] <= 1) wl_message('成团人数最小为2');
                if($goods['grouptime'] <= 0) wl_message('请填写组团时间');
                $goods['merchantid'] = $goods['sid'];
                unset($goods['sid']);
                $goods['listorder'] = $goods['sort'];
                unset($goods['sort']);
                $goods['limitstarttime'] = $goods['starttime'];
                unset($goods['starttime']);
                $goods['limitendtime'] = $goods['endtime'];
                unset($goods['endtime']);
                $goods['logo'] = $goods['thumb'];
                unset($goods['thumb']);
                $goods['adv'] = $goods['thumbs'];
                unset($goods['thumbs']);
                $goods['stock'] = $goods['num'];
                unset($goods['num']);
                $goods['falsesalenum'] = $goods['allsalenum'];
                unset($goods['allsalenum']);
                $goods['vipdiscount'] = $goods['vipprice'];
                unset($goods['vipprice']);
                $goods['specstatus'] = $goods['optionstatus'];
                unset($goods['optionstatus']);
                $goods['categoryid'] = $goods['cateid'];
                unset($goods['cateid']);
                unset($goods['pftid']);
                if($goods['is_lucky'] > 0){
                    $goods['luckynum'] = floor($goods['luckynum']);
                    if($goods['luckynum'] <= 1) {
                        $goods['luckynum'] = 1;
                    }
                    if($goods['luckynum'] > $goods['peoplenum']){
                        wl_message('幸运人数不能超过组团人数:'.$goods['peoplenum'].'人');
                    }
                    $goods['luckymoney'] = sprintf("%.2f",$goods['luckymoney']);
                }
                if (!empty($id)) {
                    Wlfightgroup::updateGoods($goods , $id);
                }
                else {
                    $id = Wlfightgroup::saveGoods($goods);
                }
            }
            else if ($plugin == 'bargain') {
                //砍价规则
                $rule_pice   = $_GPC['rule_pice'];
                $rule_start  = $_GPC['rule_start'];
                $rule_end    = $_GPC['rule_end'];
                $len         = count($rule_pice);
                $bargainrule = [];
                for ($k = 0 ; $k < $len ; $k++) {
                    $bargainrule[$k]['rule_pice']  = $rule_pice[$k];
                    $bargainrule[$k]['rule_start'] = $rule_start[$k];
                    $bargainrule[$k]['rule_end']   = $rule_end[$k];
                }
                $bargainrule    = serialize($bargainrule);
                $goods['rules'] = $bargainrule;
                $goods['bar_image'] = $_GPC['bar_image'];
                $goods['bar_bgc'] = $_GPC['bar_bgc'];

                $goods['stock'] = $goods['num'];
                unset($goods['num']);
                $goods['falsejoinnum'] = $goods['allsalenum'];
                unset($goods['allsalenum']);
                unset($goods['optionstatus']);
                unset($goods['pftid']);

                if (!empty($id)) {
                    Bargain::updateActive($goods , ['id' => $id]);
                }
                else {
                    $id = Bargain::saveActive($goods);
                }
            }

            //清理海报缓存
            Tools::clearposter();

            //保存规格
            if ($goods['optionstatus'] || $goods['specstatus']) {
                $this->spec_save($id , $plugin);
                if($plugin == 'rush'){
                    $optionList = pdo_getall(PDO_NAME."goods_option",['type'=>1,'goodsid'=>$id],['stock']);
                    $totalnum = array_sum(array_column($optionList,'stock'));
                    pdo_update('wlmerchant_rush_activity',array('num' => $totalnum),array('id' => $id));
                }else if($plugin == 'groupon'){
                    $optionList = pdo_getall(PDO_NAME."goods_option",['type'=>3,'goodsid'=>$id],['stock']);
                    $totalnum = array_sum(array_column($optionList,'stock'));
                    pdo_update('wlmerchant_groupon_activity',array('num' => $totalnum),array('id' => $id));
                }else if($plugin == 'fightgroup'){
                    $optionList = pdo_getall(PDO_NAME."goods_option",['type'=>2,'goodsid'=>$id],['stock']);
                    $totalnum = array_sum(array_column($optionList,'stock'));
                    pdo_update('wlmerchant_fightgroup_goods',array('stock' => $totalnum),array('id' => $id));
                }
            }
            $page = $_GPC['page'];
            //编辑商品成功
            $this->save_success($goods , $plugin,$page);
        }
        /*************************************************************************************************************/
        //商品分类
        $cate = pdo_getall('wlmerchant_' . $plugin . '_category' , [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid'] ,
            'is_show' => 0
        ]);
        if ($plugin == 'rush') {
            //专题分类
            $specials = pdo_getall('wlmerchant_rush_special' , ['uniacid' => $_W['uniacid'] , 'aid' => $_W['aid']]);
        }
        //运费模板
        if(is_store()){
            $express = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_express_template')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND sid IN (0,{$_W['storeid']}) ORDER BY id DESC");
        }else{
            $express = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_express_template')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ORDER BY id DESC");
        }
        //社群
        $communitylist = pdo_getall('wlmerchant_community' , [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid']
        ] , ['id' , 'communname']);
        //分销设置
        $distriset = p('distribution') ? Setting::wlsetting_read('distribution') : [];
        //分销商等级
        if($distriset['switch'] > 0){
            $dislevel = pdo_getall('wlmerchant_dislevel', array('uniacid' => $_W['uniacid']),['id','name']);
            $grouplevel = pdo_getall('wlmerchant_grouplevel', array('uniacid' => $_W['uniacid']),['id','name']);
        }
        //用户标签
        $labels = pdo_getall('wlmerchant_userlabel' , ['uniacid' => $_W['uniacid']] , [
            'id' ,
            'name'
        ] , '' , 'sort DESC');
        //一卡通等级
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");

        //商品标签
        $tag_type   = ['rush' => 1 , 'groupon' => 3 , 'bargain' => 4 , 'fightgroup' => 2];
        $presettags = pdo_getall('wlmerchant_tags' , [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid'] ,
            'type'    => $tag_type[$plugin]
        ] , ['id' , 'title'] , '' , 'sort DESC,id DESC');
        //自定义海报
        if (p('diyposter')) {
            $poster_type = ['rush' => 2 , 'groupon' => 5 , 'bargain' => 7 , 'fightgroup' => 6];
            $posters     = pdo_getall(PDO_NAME . 'poster' , [
                'uniacid' => $_W['uniacid'] ,
                'type'    => $poster_type[$plugin]
            ] , ['id' , 'title']);
        }
        //支付有礼
        if(p('paidpromotion')){
            $paidlist = pdo_getall('wlmerchant_payactive',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }
        //锦鲤抽奖
        if(agent_p('luckydraw')){
            $drawlist = pdo_getall('wlmerchant_luckydraw',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }

        if ($id > 0) {
            if ($plugin == 'rush') {
                $goods           = Rush::getSingleActive($id , '*');
                $goods['lp_set'] = unserialize($goods['lp_set']);

            }
            else if ($plugin == 'groupon') {
                $goods = Groupon::getSingleActive($id , '*');
            }
            else if ($plugin == 'bargain') {
                $goods               = Bargain::getSingleActive($id , '*');
                $goods['rules']      = unserialize($goods['rules']);
                $goods['num']        = $goods['stock'];
                $goods['allsalenum'] = $goods['falsejoinnum'];
                if(empty($goods['bar_bgc'])){
                    $goods['bar_bgc'] = '#68d3ff';
                }

            }
            else if ($plugin == 'fightgroup') {
                $goods                 = Wlfightgroup::getSingleGood($id , '*');
                $goods['sid']          = $goods['merchantid'];
                $goods['sort']         = $goods['listorder'];
                $goods['starttime']    = $goods['limitstarttime'];
                $goods['endtime']      = $goods['limitendtime'];
                $goods['thumb']        = $goods['logo'];
                $goods['thumbs']       = $goods['adv'];
                $goods['num']          = $goods['stock'];
                $goods['allsalenum']   = $goods['falsesalenum'];
                $goods['vipprice']     = $goods['vipdiscount'];
                $goods['optionstatus'] = $goods['specstatus'];
                $goods['cateid']       = $goods['categoryid'];
            }

            $merchant        = Rush::getSingleMerchant($goods['sid'] , 'id,storename,logo,groupid');
            $goods['thumbs'] = unserialize($goods['thumbs']);
            $tags            = unserialize($goods['tag']);
            $orderinfo       = unserialize($goods['orderinfo']);
            $userlabel       = unserialize($goods['userlabel']);
            $goods['level']  = unserialize($goods['level']);
            //规格
            $data     = $this->spec_html($id , $plugin);
            $html     = $data['html'];
            $allspecs = $data['allspecs'];
            $options  = $data['options'];
            if(!empty($options)){
                foreach($options as &$option){
                    $option['viparray'] = unserialize($option['viparray']);
                    $option['disarray'] = unserialize($option['disarray']);
                    $option['grouparray'] = unserialize($option['grouparray']);
                }
            }

            if ($goods['usedatestatus'] == 1) {
                $goods['week'] = unserialize($goods['week']);
            }
            if ($goods['usedatestatus'] == 2) {
                $goods['day'] = unserialize($goods['day']);
            }
            if($goods['vipstatus'] == 1){
                $viparray = unserialize($goods['viparray']);
            }
            if(!empty($goods['disarray'])){
                $disarray = unserialize($goods['disarray']);
            }
            if(!empty($goods['grouparray'])){
                $grouparray = unserialize($goods['grouparray']);
            }
            if(!empty($goods['appointarray'])){
                $goods['appointarray'] = unserialize($goods['appointarray']);
            }
        }

        if (empty($goods['starttime']) || empty($goods['endtime'])) {//初始化时间
            $goods['starttime']  = time();
            $goods['endtime']    = strtotime('+1 month');
            $goods['cutofftime'] = strtotime('+2 month');
        }
        //推广图片反序列化
        $goods['extension_img'] = unserialize($goods['extension_img']);
        //支付方式
        $goods['pay_type'] = unserialize($goods['pay_type']);
        //满减活动
        if(p('fullreduce')){
            $fullreducelist = pdo_getall('wlmerchant_fullreduce_list',array('aid' => $_W['aid'],'uniacid' => $_W['uniacid']),array('id','title'));
        }
        //获取自定义表单信息
        $formWhere = ['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']];
        if(is_store()) $formWhere['sid'] = $_W['storeid'];
        $diyFormList = pdo_getall(PDO_NAME."diyform",$formWhere,['id','title'],'','create_time DESC,id DESC');
        //富文本处理
        if(is_base64($goods['detail'])) $goods['detail']   = htmlspecialchars_decode(base64_decode($goods['detail']));
        if(is_base64($goods['describe'])) $goods['describe'] = htmlspecialchars_decode(base64_decode($goods['describe']));

        include wl_template('goodshouse/createactive');
    }

    /**
     * 添加规格子项
     */
    public function spec()
    {
        global $_W , $_GPC;
        $spec = ['id' => random(32) , 'title' => $_GPC['title']];
        include wl_template('goodshouse/spec');
    }

    /**
     * 添加规格项
     */
    public function spec_item()
    {
        global $_W , $_GPC;
        $spec     = ['id' => $_GPC['specid']];
        $specitem = ['id' => random(32) , 'title' => $_GPC['title'] , 'show' => 1];
        include wl_template('goodshouse/spec_item');
    }
    /**
     * 完善图片链接
     */
    public function jstomedia(){
        global $_W , $_GPC;
        $thumb = tomedia($_GPC['thumb']);
        die($thumb);
    }

    /**
     * 根据规格生成html
     * @param $id
     * @param $plugin
     * @param $distriset
     * @return string
     */
    private function spec_html($id , $plugin)
    {
        global $_W;
        $html = '';
        if ($plugin == 'bargain') {
            return $html;
        }
        $type = $this->spec_type($plugin);

        $allspecs = pdo_fetchall('select * from ' . tablename('wlmerchant_goods_spec') . ' where goodsid=:id AND type = :type order by displayorder asc' , [
            ':id'   => $id ,
            ':type' => $type
        ]);
        foreach ($allspecs as &$s) {
            $s['items'] = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_goods_spec_item') . "WHERE uniacid = {$_W['uniacid']} AND specid = {$s['id']} ORDER BY displayorder ASC");
        }
        unset($s);
        $options = pdo_fetchall('select * from ' . tablename('wlmerchant_goods_option') . ' where goodsid=:id and type = :type order by id asc' , [
            ':id'   => $id ,
            ':type' => $type
        ]);
        $specs   = [];
        if (0 < count($options)) {
            $specitemids = explode('_' , $options[0]['specs']);
            foreach ($specitemids as $itemid) {
                foreach ($allspecs as $ss) {
                    $items = $ss['items'];
                    foreach ($items as $it) {
                        while ($it['id'] == $itemid) {
                            $specs[] = $ss;
                            break;
                        }
                    }
                }
            }
            $html     = '';
            $html     .= '<table class="table table-bordered table-condensed">';
            $html     .= '<thead>';
            $html     .= '<tr class="active">';
            $len      = count($specs);
            $newlen   = 1;
            $h        = [];
            $rowspans = [];
            $i        = 0;
            while ($i < $len) {
                $html    .= '<th>' . $specs[$i]['title'] . '</th>';
                $itemlen = count($specs[$i]['items']);
                if ($itemlen <= 0) {
                    $itemlen = 1;
                }
                $newlen *= $itemlen;
                $h      = [];
                $j      = 0;
                while ($j < $newlen) {
                    $h[$i][$j] = [];
                    ++$j;
                }
                $l            = count($specs[$i]['items']);
                $rowspans[$i] = 1;
                $j            = $i + 1;
                while ($j < $len) {
                    $rowspans[$i] *= count($specs[$j]['items']);
                    ++$j;
                }
                ++$i;
            }
            //已售
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">已售</div></div></th>';
            //缩略图
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">预览图</div></div></th>';

            if ($plugin == 'fightgroup') {
                $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">总数</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_stock_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_stock\');"></a></span></div></div></th>';
                $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">拼团价</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_price_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_price\');"></a></span></div></div></th>';
                $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">单购价</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_vipprice_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_vipprice\');"></a></span></div></div></th>';
                if(!is_store()){
                    $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">结算价</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_settlementmoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_settlementmoney\');"></a></span></div></div></th>';
                }
                //$html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">会员结算价</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_vipsettlementmoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_vipsettlementmoney\');"></a></span></div></div></th>';
                //if(!is_store()) $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">一级分销</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_onedismoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_onedismoney\');"></a></span></div></div></th>';
            }
            else {
                $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">总数</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_stock_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_stock\');"></a></span></div></div></th>';
                $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">抢购价</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_price_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_price\');"></a></span></div></div></th>';
                //$html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">会员价</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_vipprice_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_vipprice\');"></a></span></div></div></th>';
                if(!is_store()) {
                    $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">结算价</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_settlementmoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_settlementmoney\');"></a></span></div></div></th>';
                }
                //$html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">会员结算价</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_vipsettlementmoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_vipsettlementmoney\');"></a></span></div></div></th>';
                //if(!is_store()) $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">一级分销</div><div class="input-group"><input type="number" min="0" class="form-control input-sm option_onedismoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_onedismoney\');"></a></span></div></div></th>';
            }

//            if ($distriset['ranknum'] > 1) {
//                if(!is_store()) $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">二级分销</div><div class="input-group"><input type="number" class="form-control input-sm option_twodismoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_twodismoney\');"></a></span></div></div></th>';
//            }
//            if ($distriset['ranknum'] > 2) {
//                if(!is_store()) $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">三级分销</div><div class="input-group"><input type="number" class="form-control input-sm option_threedismoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_threedismoney\');"></a></span></div></div></th>';
//            }
            $html .= '</tr></thead>';
            $m    = 0;
            while ($m < $len) {
                $k   = 0;
                $kid = 0;
                $n   = 0;
                $j   = 0;
                while ($j < $newlen) {
                    $rowspan = $rowspans[$m];
                    if (($j % $rowspan) == 0) {
                        $h[$m][$j] = [
                            'html' => '<td class=\'full\' rowspan=\'' . $rowspan . '\'>' . $specs[$m]['items'][$kid]['title'] . '</td>' ,
                            'id'   => $specs[$m]['items'][$kid]['id']
                        ];
                    }
                    else {
                        $h[$m][$j] = ['html' => '' , 'id' => $specs[$m]['items'][$kid]['id']];
                    }
                    ++$n;
                    if ($n == $rowspan) {
                        ++$kid;
                        if ((count($specs[$m]['items']) - 1) < $kid) {
                            $kid = 0;
                        }
                        $n = 0;
                    }
                    ++$j;
                }
                ++$m;
            }
            $hh = '';
            $i  = 0;
            while ($i < $newlen) {
                $hh  .= '<tr>';
                $ids = [];
                $j   = 0;
                while ($j < $len) {
                    $hh .= $h[$j][$i]['html'];

                    $ids[] = $h[$j][$i]['id'];
                    ++$j;
                }
                $ids = implode('_' , $ids);
                $val = [
                    'id'                 => '' ,
                    'title'              => '' ,
                    'stock'              => '' ,
                    'price'              => '' ,
                    'vipprice'           => '' ,
                    'settlementmoney'    => '' ,
                    'vipsettlementmoney' => '' ,
                    'onedismoney'        => '' ,
                    'twodismoney'        => '' ,
                    'threedismoney'      => ''
                ];
                foreach ($options as $o) {
                    while ($ids === $o['specs']) {
                        if ($type == 1) {
                            //抢购商品的销量
                            $stopBuyNum = WeliamWeChat::getSalesNum(1,$id,$o['id'],1,0);
                        }else if($type == 2){
                            $stopBuyNum = WeliamWeChat::getSalesNum(3,$id,$o['id'],1,0);
                        }else if($type == 3){
                            $stopBuyNum = WeliamWeChat::getSalesNum(2,$id,$o['id'],1,0);
                        }
                        $stopBuyNum = !empty($stopBuyNum) ? $stopBuyNum : 0;
                        $val = [
                            'id'                 => $o['id'] ,
                            'title'              => $o['title'] ,
                            'stock'              => $o['stock'] ,
                            'price'              => $o['price'] ,
                            'vipprice'           => $o['vipprice'] ,
                            'settlementmoney'    => $o['settlementmoney'] ,
                            'vipsettlementmoney' => $o['vipsettlementmoney'] ,
                            'onedismoney'        => $o['onedismoney'] ,
                            'twodismoney'        => $o['twodismoney'] ,
                            'threedismoney'      => $o['threedismoney'],
                            'thumb'              => $o['thumb'],
                            'salenum'            => $stopBuyNum
                        ];
                        break;
                    }
                }
                //已售
                $hh .= '<td>';
                $hh .= '<div class=""><div style="padding-bottom:10px;text-align:center;">'.$val['salenum'].'</div></div>';
                $hh .= '</td>';
                //图片
                $hh .= '<td>';
                $hh .= '<input type="hidden" class = "option_thumb option_thumb_' . $ids .'" data-name="option_thumb_' . $ids .'" value="'.$val['thumb'].'" id="cimg-'. $ids .'" />';
                $hh .= '<img style="width:32px;height:32px;cursor: pointer"';
                $hh .= 'data-toggle="selectAttachment" data-input="#cimg-'. $ids .'" data-img="#pimg-'. $ids .'" id="pimg-'. $ids .'"';
                $hh .= 'src="' .tomedia($val['thumb']).'" style="width:100%;"';
                $hh .= 'data-toggle="popover" data-html ="true" data-placement="top"  data-trigger ="hover" data-content="<img src='.tomedia($val['thumb']).' style="width:100px;height:100px;" /> ';
                $hh .= '</td>';


                $hh .= '<td>';
                $hh .= '<input type="number" min="0" data-name="option_stock_' . $ids . '"  type="text" class="form-control option_stock option_stock_' . $ids . '" value="' . $val['stock'] . '"/>';
                $hh .= '</td>';
                $hh .= '<input data-name="option_id_' . $ids . '"  type="hidden" class="form-control option_id option_id_' . $ids . '" value="' . $val['id'] . '"/>';
                $hh .= '<input data-name="option_ids"  type="hidden" class="form-control option_ids option_ids_' . $ids . '" value="' . $ids . '"/>';
                $hh .= '<input data-name="option_title_' . $ids . '"  type="hidden" class="form-control option_title option_title_' . $ids . '" value="' . $val['title'] . '"/>';
                $hh .= '<td><input type="number" min="0" data-name="option_price_' . $ids . '" type="text" class="form-control option_price option_price_' . $ids . '" value="' . $val['price'] . '"/></td>';
                if($plugin == 'fightgroup'){
                    $hh .= '<td><input type="number" min="0" data-name="option_vipprice_' . $ids . '" type="text" class="form-control option_vipprice option_vipprice_' . $ids . '" value="' . $val['vipprice'] . '"/></td>';
                }
                if(!is_store()) {
                    $hh .= '<td><input type="number" min="0" data-name="option_settlementmoney_' . $ids . '" type="text" class="form-control option_settlementmoney option_settlementmoney_' . $ids . '" " value="' . $val['settlementmoney'] . '"/></td>';
                }
                //$hh .= '<td><input type="number" min="0" data-name="option_vipsettlementmoney_' . $ids . '" type="text" class="form-control option_vipsettlementmoney option_vipsettlementmoney_' . $ids . '" " value="' . $val['vipsettlementmoney'] . '"/></td>';
//                if(!is_store()) $hh .= '<td><input type="number" min="0" data-name="option_onedismoney_' . $ids . '" type="text" class="form-control option_onedismoney option_onedismoney_' . $ids . '" " value="' . $val['onedismoney'] . '"/></td>';
//                if ($distriset['ranknum'] > 1) {
//                    if(!is_store()) $hh .= '<td><input type="number" min="0" data-name="option_twodismoney_' . $ids . '" type="text" class="form-control option_twodismoney option_twodismoney_' . $ids . '" " value="' . $val['twodismoney'] . '"/></td>';
//                }
//                if ($distriset['ranknum'] > 2) {
//                    if(!is_store()) $hh .= '<td><input type="number" min="0" data-name="option_threedismoney_' . $ids . '" type="text" class="form-control option_threedismoney option_threedismoney_' . $ids . '" " value="' . $val['threedismoney'] . '"/></td>';
//                }
                $hh .= '</tr>';
                ++$i;
            }
            $html .= $hh;
            $html .= '</table>';
        }
        $data['html']     = $html;
        $data['allspecs'] = $allspecs;
        $data['options']  = $options;
        return $data;
    }

    /**
     * 插件规格对应的类型
     * @param $plugin
     * @return mixed
     */
    private function spec_type($plugin)
    {
        $spec_types = ['rush' => 1 , 'groupon' => 3 , 'fightgroup' => 2];
        return $spec_types[$plugin];
    }

    /**
     * 规格保存
     * @param $id
     * @param $plugin
     */
    private function spec_save($id , $plugin)
    {
        global $_W,$_GPC;
        $type        = $this->spec_type($plugin);
        $totalstocks = 0;
        $spec_ids    = $_POST['spec_id'];
        $spec_titles = $_POST['spec_title'];
        $specids     = [];
        $len         = count($spec_ids);
        $specids     = [];
        $spec_items  = [];
        $k           = 0;
        while ($k < $len) {
            $spec_id     = '';
            $get_spec_id = $spec_ids[$k];
            $a           = [
                'uniacid'      => $_W['uniacid'] ,
                'goodsid'      => $id ,
                'displayorder' => $k ,
                'title'        => $spec_titles[$get_spec_id]
            ];
            if (is_numeric($get_spec_id)) {  //判断是否是数字或字符串
                pdo_update('wlmerchant_goods_spec' , $a , ['id' => $get_spec_id]);
                $spec_id = $get_spec_id;
            }
            else {
                $a['type'] = $type;
                pdo_insert('wlmerchant_goods_spec' , $a);
                $spec_id = pdo_insertid();
            }
            $spec_item_ids       = $_POST['spec_item_id_' . $get_spec_id];
            $spec_item_titles    = $_POST['spec_item_title_' . $get_spec_id];
            $spec_item_shows     = $_POST['spec_item_show_' . $get_spec_id];
            $spec_item_virtuals  = $_POST['spec_item_virtual_' . $get_spec_id];
            $itemlen             = count($spec_item_ids);
            $itemids             = [];
            $n                   = 0;
            while ($n < $itemlen) {
                $item_id     = '';
                $get_item_id = $spec_item_ids[$n];
                $d           = [
                    'uniacid'      => $_W['uniacid'] ,
                    'specid'       => $spec_id ,
                    'displayorder' => $n ,
                    'title'        => $spec_item_titles[$n] ,
                    'show'         => $spec_item_shows[$n] ,
                ];
                if (is_numeric($get_item_id)) {
                    pdo_update('wlmerchant_goods_spec_item' , $d , ['id' => $get_item_id]);
                    $item_id = $get_item_id;
                }
                else {
                    pdo_insert('wlmerchant_goods_spec_item' , $d);
                    $item_id = pdo_insertid();
                }
                $itemids[]    = $item_id;
                $d['get_id']  = $get_item_id;
                $d['id']      = $item_id;
                $spec_items[] = $d;
                ++$n;
            }
            if (0 < count($itemids)) {
                pdo_query('delete from ' . tablename('wlmerchant_goods_spec_item') . ' where  specid=' . $spec_id . ' and id not in (' . implode(',' , $itemids) . ')');
            }
            else {
                pdo_query('delete from ' . tablename('wlmerchant_goods_spec_item') . ' where  specid=' . $spec_id);
            }
            pdo_update('wlmerchant_goods_spec' , ['content' => serialize($itemids)] , ['id' => $spec_id]);
            $specids[] = $spec_id;
            ++$k;
        }
        if (0 < count($specids)) {
            pdo_query('delete from ' . tablename('wlmerchant_goods_spec') . ' where  type = ' . $type . ' and goodsid=' . $id . ' and id not in (' . implode(',' , $specids) . ')');
        }
        else {
            pdo_query('delete from ' . tablename('wlmerchant_goods_spec') . ' where  type = ' . $type . ' and goodsid=' . $id);
        }

        $optionArray = json_decode($_POST['optionArray'] , true);
        $option_idss = $optionArray['option_ids'];
        $len         = count($option_idss);
        $optionids   = [];
        $k           = 0;
        while ($k < $len) {
            $option_id     = '';
            $ids           = $option_idss[$k];
            $get_option_id = $optionArray['option_id'][$k];
            $idsarr        = explode('_' , $ids);
            $newids        = [];
            foreach ($idsarr as $key => $ida) {
                foreach ($spec_items as $it) {
                    while ($it['get_id'] == $ida) {
                        $newids[] = $it['id'];
                        break;
                    }
                }
            }
            $newids      = implode('_' , $newids);
            $a           = [
                'uniacid'            => $_W['uniacid'] ,
                'thumb'              => $optionArray['option_thumb'][$k] ,
                'stock'              => $optionArray['option_stock'][$k] ,
                'title'              => $optionArray['option_title'][$k] ,
                'price'              => $optionArray['option_price'][$k] ,
                'vipprice'           => $optionArray['option_vipprice'][$k] ,
//                'settlementmoney'    => $optionArray['option_settlementmoney'][$k] ,
//                'vipsettlementmoney' => $optionArray['option_vipsettlementmoney'][$k] ,
//                'onedismoney'        => $optionArray['option_onedismoney'][$k] ,
//                'twodismoney'        => $optionArray['option_twodismoney'][$k] ,
//                'threedismoney'      => $optionArray['option_threedismoney'][$k] ,
                'goodsid'            => $id ,
                'specs'              => $newids ,
                'type'               => $type
            ];
            if(!is_store()){
                $a['settlementmoney'] = $optionArray['option_settlementmoney'][$k];
            }
            //会员减免
            $viparray = [];
            $vipleidkword = 'vipleid'.$get_option_id;
            $vippricekword = 'vipprice'.$get_option_id;
            $storesetkword = 'storeset'.$get_option_id;
            $vipleid = $_GPC[$vipleidkword];
            $vipprice = $_GPC[$vippricekword];
            $storeset = $_GPC[$storesetkword];
            foreach($vipleid as $key => $vle){
                $vipa['vipprice'] = sprintf("%.2f",$vipprice[$key]);
                if(is_store()){
                    $vipa['storeset'] = $vipa['vipprice'];
                }else{
                    $vipa['storeset'] = sprintf("%.2f",$storeset[$key]);
                }
                $viparray[$vle] = $vipa;
            }
            $a['viparray'] = serialize($viparray);
            //分销佣金
            $disarray = [];
            $disleidkword = 'disleid'.$get_option_id;
            $onedismoneykword = 'onedismoney'.$get_option_id;
            $twodismoneykword = 'twodismoney'.$get_option_id;
            $disleid = $_GPC[$disleidkword];
            $onedismoney = $_GPC[$onedismoneykword];
            $twodismoney = $_GPC[$twodismoneykword];
            foreach($disleid as $keyy => $dddle){
                $dddleaa['onedismoney'] = sprintf("%.2f",$onedismoney[$keyy]);
                $dddleaa['twodismoney'] = sprintf("%.2f",$twodismoney[$keyy]);
                $disarray[$dddle] = $dddleaa;
            }
            $a['disarray'] = serialize($disarray);
            //团长分红
            $grouparray = [];
            $groupleidkword = 'groupleid'.$get_option_id;
            $onegroupmoneykword = 'onegroupmoney'.$get_option_id;
            $twogroupmoneykword = 'twogroupmoney'.$get_option_id;
            $groupleid = $_GPC[$groupleidkword];
            $onegroupmoney = $_GPC[$onegroupmoneykword];
            $twogroupmoney = $_GPC[$twogroupmoneykword];
            foreach($groupleid as $grkeyy => $grdle){
                $grdleaa['onegroupmoney'] = sprintf("%.2f",$onegroupmoney[$grkeyy]);
                $grdleaa['twogroupmoney'] = sprintf("%.2f",$twogroupmoney[$grkeyy]);
                $grouparray[$grdle] = $grdleaa;
            }
            $a['grouparray'] = serialize($grouparray);
            $shareholdermoney = 'shareholdermoney'.$get_option_id;
            $a['shareholdermoney'] = $_GPC[$shareholdermoney];


            $totalstocks += $a['stock'];
            if (empty($get_option_id)) {
                pdo_insert('wlmerchant_goods_option' , $a);
                $option_id = pdo_insertid();
            }
            else {
                pdo_update('wlmerchant_goods_option' , $a , ['id' => $get_option_id]);
                $option_id = $get_option_id;
            }
            $optionids[] = $option_id;
            ++$k;
        }
        if (0 < count($optionids)) {
            pdo_query('delete from ' . tablename('wlmerchant_goods_option') . ' where type = ' . $type . ' AND goodsid=' . $id . ' and id not in ( ' . implode(',' , $optionids) . ')');
        }
        else {
            pdo_query('delete from ' . tablename('wlmerchant_goods_option') . ' where type = ' . $type . ' AND goodsid=' . $id);
        }
    }

    private function save_success($goods , $plugin,$page = 1)
    {
        global $_W;
        $plugins = [
            'rush'       => ['name' => '抢购' , 'url' => web_url('rush/active/activelist',array('page'=>$page))] ,
            'groupon'    => ['name' => '团购' , 'url' => web_url('groupon/active/activelist',array('page'=>$page))] ,
            'fightgroup' => ['name' => '拼团' , 'url' => web_url('wlfightgroup/fightgoods/ptgoodslist',array('page'=>$page))] ,
            'bargain'    => ['name' => '砍价' , 'url' => web_url('bargain/bargain_web/activitylist',array('page'=>$page))] ,
        ];

        if ($goods['status'] == 5) {
            $storename = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $_W['storeid']] , 'storename');
            //审核通知代理
            $first   = '您好，您有一个待审核任务需要处理';
            $type    = '审核商品';
            $content = $plugins[$plugin]['name'] . '商品:' . $goods['name'];
            $status  = '待审核';
            $remark  = '商户[' . $storename . ']上传了一个' . $plugins[$plugin]['name'] . '商品待审核,请管理员尽快前往后台审核';
            News::noticeAgent('storegood' , $_W['aid'] , $first , $type , $content , $status , $remark , time() , '');

        }
        wl_message('保存成功！' , $plugins[$plugin]['url'] , 'success');
    }

    public function selectMerchant()
    {
        global $_W , $_GPC;
        $where            = [];
        $where['uniacid'] = $_W['uniacid'];
        $where['aid']     = $_W['aid'];
        $where['status']  = 2;
        $where['enabled'] = 1;
        if ($_GPC['keyword']) $where['@storename@'] = $_GPC['keyword'];
        if ($_GPC['enabled']) $where['enabled'] = $_GPC['enabled'];
        $merchants = Rush::getNumMerchant('id,storename,logo' , $where , 'ID DESC' , 0 , 0 , 0);
        $merchants = $merchants[0];
        foreach ($merchants as $key => &$va) {
            $va['logo'] = tomedia($va['logo']);
        }
        include wl_template('goodshouse/selectMerchant');
    }

    public function rules(){
        include wl_template('goodshouse/rules');
    }

    public function appointarray(){
        global $_W,$_GPC;
        $houseflag = $_GPC['house'];
        include wl_template('goodshouse/appointhtml');
    }

    /**
     * 核销码列表
     */
    public function checklist(){
        global $_W , $_GPC;
        $id = $_GPC['id'];
        $plugin = $_GPC['plugin'];
        $status = $_GPC['status']; //0全部 1已使用 2未使用
        $pindex = max(1,intval($_GPC['page']));
        if(empty($id) || empty($plugin)){
            wl_message('无商品信息', referer(),'error');
        }
        //商品信息
        if($plugin == 'rush'){
            $goodinfo = pdo_get('wlmerchant_rush_activity',array('id' => $id),array('name','thumb','sid'));
            $goodinfo['thumb'] = tomedia($goodinfo['thumb']);
        }else if($plugin == 'groupon'){
            $goodinfo = pdo_get('wlmerchant_groupon_activity',array('id' => $id),array('name','thumb','sid'));
            $goodinfo['thumb'] = tomedia($goodinfo['thumb']);
        }else if($plugin == 'wlfightgroup'){
            $goodinfo = pdo_get('wlmerchant_fightgroup_goods',array('id' => $id),array('name','logo','merchantid'));
            $goodinfo['sid'] = $goodinfo['merchantid'];
            $goodinfo['thumb'] = tomedia($goodinfo['logo']);
        }else if($plugin == 'bargain'){
            $goodinfo = pdo_get('wlmerchant_bargain_activity',array('id' => $id),array('name','thumb','sid'));
            $goodinfo['thumb'] = tomedia($goodinfo['thumb']);
        }else if($plugin == 'coupon'){
            $goodinfo = pdo_get('wlmerchant_couponlist',array('id' => $id),array('title','logo','merchantid'));
            $goodinfo['name'] = $goodinfo['title'];
            $goodinfo['sid'] = $goodinfo['merchantid'];
            $goodinfo['thumb'] = tomedia($goodinfo['logo']);
        }else if($plugin == 'activity'){
            $goodinfo = pdo_get('wlmerchant_activitylist',array('id' => $id),array('title','thumb','sid'));
            $goodinfo['name'] = $goodinfo['title'];
            $goodinfo['thumb'] = tomedia($goodinfo['thumb']);
        }
        //商户信息
        $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $goodinfo['sid']),array('storename','logo'));
        $merchant['logo'] = tomedia($merchant['logo']);
        //条件筛选
        $where = [
            'uniacid' => $_W['uniacid'],
            'goodsid' => $id,
            'plugin'  => $plugin
        ];
        if($status == 1){
            $where['status'] = 1;
        }else if($status == 2){
            $where['status'] = 0;
        }
        $lists = Util::getNumData('*','wlmerchant_checkcodelist',$where,'id DESC',$pindex,25,1);
        $list = $lists[0];
        $pager = $lists[1];
        $tatal = $lists[2];
        $typeList = [
            'rush' => 1,
            'groupon' => 10,
            'wlfightgroup' => 2,
            'bargain' => 12
        ];
        foreach ($list as &$li){
            $li['type'] = $typeList[$li['plugin']];
        }

        include wl_template('goodshouse/checklist');
    }
    /**
     * 核销码导入
     */
    public function importCode(){
        global $_W , $_GPC;
        $gid = $_GPC['gid'];
        $plugin = $_GPC['plugin'];
        if(empty($gid) || empty($plugin)){
            wl_json(0, '缺少商品数据，请刷新重试');
        }
        $filename = $_FILES['file']['name'];
        $filename = substr($filename, -4, 4);
        if (empty ($filename)) {
            wl_json(0, '请选择要导入的CSV文件');
        }
        if ($filename !== '.csv') {
            wl_json(0, '请选择CSV文件');
        }
        $file_path = $_FILES['file']['tmp_name'];
        $file_size = filesize($file_path);    //文件大小
        if ($file_size == 0) {
            wl_json(0, '没有任何数据');
        }
        $info = util_csv::read_csv_lines($file_path, 9999, 0);
        foreach ($info as $k => $v) {
            $checkcode = trim($v[0]);
            if(!empty($checkcode)){
                $flag = pdo_getcolumn(PDO_NAME.'checkcodelist',array('uniacid'=>$_W['uniacid'],'checkcode'=>$checkcode),'id');
                if(empty($flag)){
                    $data = [
                        'uniacid' => $_W['uniacid'],
                        'checkcode' => $checkcode,
                        'goodsid' => $gid,
                        'plugin' => $plugin,
                        'status' => 0
                    ];
                    pdo_insert(PDO_NAME . 'checkcodelist', $data);
                }
            }
        }
        if($plugin == 'rush'){
            pdo_update('wlmerchant_rush_activity',array('checkcodeflag' => 1),array('id' => $gid));
        }else if($plugin == 'groupon'){
            pdo_update('wlmerchant_groupon_activity',array('checkcodeflag' => 1),array('id' => $gid));
        }else if($plugin == 'wlfightgroup'){
            pdo_update('wlmerchant_fightgroup_goods',array('checkcodeflag' => 1),array('id' => $gid));
        }else if($plugin == 'bargain'){
            pdo_update('wlmerchant_bargain_activity',array('checkcodeflag' => 1),array('id' => $gid));
        }else if($plugin == 'coupon'){
            pdo_update('wlmerchant_couponlist',array('checkcodeflag' => 1),array('id' => $gid));
        }else if($plugin == 'activity'){
            pdo_update('wlmerchant_activitylist',array('checkcodeflag' => 1),array('id' => $gid));
        }
        wl_json(1, '导入成功');
    }

    public function deletecheckcode(){
        global $_W , $_GPC;
        $ids = $_GPC['ids'];
        $id = $_GPC['id'];
        if(!empty($ids)){
            foreach ($ids as $v) {
                pdo_delete('wlmerchant_checkcodelist',array('id'=>$v));
            }
        }else{
            pdo_delete('wlmerchant_checkcodelist',array('id'=>$id));
        }
        show_json(1, '操作成功');
    }

    public function emptycheckcode(){
        global $_W , $_GPC;
        $id = $_GPC['gid'];
        $plugin = $_GPC['plugin'];
        if(empty($id) || empty($plugin)){
            show_json(0, '无关键参数，请刷新页面重试');
        }
        $res = pdo_delete('wlmerchant_checkcodelist',array('goodsid'=>$id,'plugin'=>$plugin,'uniacid' => $_W['uniacid']));
        if($res){
            //修改商品状态
            if($plugin == 'rush'){
                pdo_update('wlmerchant_rush_activity',array('checkcodeflag' => 0),array('id' => $id));
            }else if($plugin == 'groupon'){
                pdo_update('wlmerchant_groupon_activity',array('checkcodeflag' => 0),array('id' => $id));
            }else if($plugin == 'wlfightgroup'){
                pdo_update('wlmerchant_fightgroup_goods',array('checkcodeflag' => 0),array('id' => $id));
            }else if($plugin == 'bargain'){
                pdo_update('wlmerchant_bargain_activity',array('checkcodeflag' => 0),array('id' => $id));
            }else if($plugin == 'coupon'){
                pdo_update('wlmerchant_couponlist',array('checkcodeflag' => 0),array('id' => $id));
            }else if($plugin == 'activity'){
                pdo_update('wlmerchant_activitylist',array('checkcodeflag' => 0),array('id' => $id));
            }
            show_json(1, '操作成功');
        }else{
            show_json(0, '操作失败，请刷新页面重试');
        }
    }

}