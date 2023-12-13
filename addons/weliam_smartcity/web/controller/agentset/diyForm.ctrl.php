<?php
defined('IN_IA') or exit('Access Denied');
/**
 * Comment: 自定义表单控制器
 * Author: zzw
 * Class DiyForm_WeliamController
 */
class DiyForm_WeliamController{
    /**
     * Comment: 自定义表单列表
     * Author: zzw
     * Date: 2020/11/19 10:42
     */
    public function index(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $title     = $_GPC['title'] ? : '';//表单名称
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        if(is_store()) $where .= " AND a.sid = {$_W['storeid']} ";
        if($title) $where .= " AND a.title LIKE '%{$title}%'";
        //列表信息获取
        $field = "a.id,a.title,a.create_time,a.update_time,b.storename";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."diyform")
            ." as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
            ." as b ON a.sid = b.id {$where}";
        $list = pdo_fetchall($sql." ORDER BY a.update_time DESC,a.create_time DESC limit {$pageStart},{$pageIndex}");
        //总数信息获取
        $countSql = str_replace($field,"count(*)",$sql);
        $total = pdo_fetchcolumn($countSql);
        //分页操作
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('agentset/diy_form/list');
    }
    /**
     * Comment: 添加|编辑 自定义表单
     * Author: zzw
     * Date: 2020/11/19 10:56
     */
    public function edit(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] ? : '';
        if($_W['aid'] == 0 && p('attestation')){
            $attflag = 1;
        }else{
            $attflag = 0;
        }
        //添加储存/编辑修改 信息处理
        if($_W['ispost']){
            //参数信息获取
            $data = $_GPC['data'] OR wl_json(0,'请添加组件');
            $t_name = [];
            foreach($data['list'] as $da){
                $t_name[] = $da['data']['title'];
            }
            if (count($t_name) != count(array_unique($t_name))) {
                wl_json(0,'组件请勿设置相同标题');
            }
            //信息拼装
            $params = [
                'title'       => $data['base']['title'] ? : '自定义表单',
                'info'        => base64_encode(json_encode($data,JSON_UNESCAPED_UNICODE)),
                'update_time' => time()
            ];
            //判断是添加还是修改
            if($id > 0){
                //修改操作
                pdo_update(PDO_NAME."diyform",$params,['id'=>$id]);
            }else{
                //添加操作
                $params['uniacid'] = $_W['uniacid'];
                $params['aid'] = $_W['aid'];
                $params['create_time'] = time();
                if(is_store()) $params['sid'] = $_W['storeid'];
                pdo_insert(PDO_NAME."diyform",$params);
            }

            wl_json(1,$id > 0 ? '编辑成功' : '添加成功');
        }
        //id存在 编辑信息  获取表单信息
        if($id > 0){
            $info = pdo_getcolumn(PDO_NAME."diyform",['id'=>$id],'info');
            $info = json_decode(base64_decode($info), true);//页面的配置信息
        }



        include wl_template('agentset/diy_form/edit');
    }
    /**
     * Comment: 删除表单
     * Author: zzw
     * Date: 2020/11/19 10:58
     */
    public function delete(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, '参数错误，请刷新重试!');
        //删除内容
        $res = pdo_delete(PDO_NAME."diyform",['id'=>$id]);
        if ($res) show_json(1 , '删除成功');
        else show_json(0 , '删除失败，请刷新重试');
    }
    /**
     * Comment: 自定义表单记录
     * Author: wlf
     * Date: 2022/04/06 14:27
     */
    public function record(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, '参数错误，请刷新重试!');
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $where = ['uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'formid' => $id];
        //搜索
        if (!empty($_GPC['keyword'])) {
            $keyword = $_GPC['keyword'];
            if ($_GPC['keywordtype'] == 1) {
                $params[':nickname'] = "%{$keyword}%";
                $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
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
                    $where['mid#'] = $sids;
                } else {
                    $where['mid#'] = "(0)";
                }
            } else if ($_GPC['keywordtype'] == 2) {
                $where['mid@'] = intval($keyword);
            } else if ($_GPC['keywordtype'] == 3){
                $params[':mobile'] = "%{$keyword}%";
                $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND mobile LIKE :mobile", $params);
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
                    $where['mid#'] = $sids;
                } else {
                    $where['mid#'] = "(0)";
                }
            }
        }
        //时间
        if ($_GPC['time_limit'] && $_GPC['timetype']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where['dotime>'] = $starttime;
            $where['dotime<'] = $endtime + 86399;
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        if($_GPC['export']){
            $this -> export($where,$id);
        }

        $list = Util::getNumData('*', PDO_NAME.'diyform_list', $where, 'dotime DESC', $page, $pageIndex, 1);

        $pager = $list[1];
        $list = $list[0];

        foreach($list as &$li){
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('nickname','encodename','avatar','mobile'));
            $li['nickname'] = empty($member['encodename']) ? $member['nickname'] : base64_decode($member['encodename']);
            $li['mobile'] = $member['mobile'];
            $li['avatar'] = tomedia($member['avatar']);
            if($li['plugin'] == 'pocket'){
                $li['infoid'] = pdo_getcolumn(PDO_NAME.'pocket_informations',array('diyformid'=> $li['id'] ),'id');
            }
        }

        include wl_template('agentset/diy_form/record');

    }

    /**
     * Comment: 记录导出
     * Author: wlf
     * Date: 2022/04/07 16:00
     */
    public function export($where,$id){
        global $_W,$_GPC;

        $list = Util::getNumData('*', PDO_NAME.'diyform_list', $where, 'dotime DESC');
        $list = $list[0];
        foreach($list as &$li){
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('mobile','nickname','encodename'));
            $li['nickname'] = empty($member['encodename']) ? $member['nickname'] : base64_decode($member['encodename']);
            $li['mobile'] = "\t".$member['mobile']."\t";
            $li['dotime'] = date('Y-m-d H:i:s',$li['dotime']);
            $li['listinfo'] = unserialize($li['listinfo']);
            foreach ($li['listinfo'] as $zzw => $in){
                if($in['id'] == 'checkbox' || $in['id'] == 'img'){
                    $li['zzw'.$zzw] = implode(",", $in['data']);
                }else if($in['id'] == 'datetime' || $in['id'] == 'city'){
                    $li['zzw'.$zzw] = implode("-", $in['data']);
                } else{
                    $li['zzw'.$zzw] = "\t".$in['data']."\t";
                }
            }
        }

        /* 输出表头 */
        $filter = array(
            'id'  => '记录id',//U
            'nickname' => '用户昵称',
            'mid'  => '用户mid',
            'mobile' => '用户电话',
            'dotime' => '填表时间'
        );

        $diyforminfo = pdo_get('wlmerchant_diyform',array('id' => $id),array('info','title'));
        $moinfo = json_decode(base64_decode($diyforminfo['info']) , true);
        $diylist = $moinfo['list'];
        $diylist = array_values($diylist);
        foreach ($diylist as $wlf => $dyli){
            $filter['zzw'.$wlf] = $dyli['data']['title'];
        }

        $data = array();
        for ($i=0; $i < count($list) ; $i++) {
            foreach ($filter as $key => $title) {
                $data[$i][$key] = $list[$i][$key];
            }
        }

        util_csv::export_csv_2($data, $filter,$diyforminfo['title'].'记录.csv');
        exit();
    }


    /**
     * Comment: 删除记录
     * Author: wlf
     * Date: 2022/04/07 11:33
     */
    public function recordDelete(){
        global $_W,$_GPC;
        $id = $_GPC['id'] OR show_json(0, '参数错误，请刷新重试!');

        $res = pdo_delete('wlmerchant_diyform_list',array('id'=>$id));
        if($res){
            show_json(1, '删除成功');
        }else{
            show_json(0, '删除失败，请刷新重试!');
        }
    }


    /**
     * Comment: 自定义表单记录详情与编辑
     * Author: wlf
     * Date: 2022/04/06 15:29
     */
    public function recordedit(){
        global $_W,$_GPC;
        $id = $_GPC['id'] OR show_json(0, '参数错误，请刷新重试!');
        
        $info = pdo_get('wlmerchant_diyform_list',array('id' => $id),array('mid','formid','listinfo'));
        $moreinfo = unserialize($info['listinfo']);
        $member = pdo_get('wlmerchant_member',array('id' => $info['mid']),array('nickname','encodename','avatar','mobile'));
        $member['nickname'] = empty($member['encodename']) ? $member['nickname'] : base64_decode($member['encodename']);

        $diyforminfo = pdo_get('wlmerchant_diyform',array('id' => $info['formid']),array('info'));
        $moinfo = json_decode(base64_decode($diyforminfo['info']) , true);
        $list = $moinfo['list'];
        $list = array_values($list);
        $newinfo = [];
        foreach ($moreinfo as $mminfo){
            $newinfo[$mminfo['title']] = $mminfo;
        }

        foreach ($list as &$lis){
            if(empty($newinfo[$lis['data']['title']]['key'])){
                $moreinfo[] = [
                    'id' =>   $lis['id'],
                    'key' =>  $lis['key'],
                    'data' => '',
                    'title' => $lis['data']['title'],
                    'att_show' => $lis['data']['att_show']
                ];

                if($lis['id'] == 'city'){
                    $cityflag = 1;
                    $city_name = $lis['val'][1];
                    $area_name = $lis['val'][2];
                }
                $lis['keyinfo'] = 'newmoreinfo['.$lis['key'].']';

            }else{
                $lis['val'] = $newinfo[$lis['data']['title']]['data'];
                $lis['key'] = $newinfo[$lis['data']['title']]['key'];
                if($lis['id'] == 'city'){
                    $cityflag = 1;
                    $city_name = $lis['val'][1];
                    $area_name = $lis['val'][2];
                }
                $lis['keyinfo'] = 'newmoreinfo['.$lis['key'].']';
            }
        }

        //查询地区
        if($cityflag > 0 ){
            $AreaTab = tablename(PDO_NAME . "area");
            $orderBy = " ORDER BY id ASC ";
            $province = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 1 " . $orderBy);

            $province_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 2 AND name = '{$city_name}'");
            $city = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 2 AND pid = {$province_id}" . $orderBy);

            $city_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 3 AND name = '{$area_name}'");
            $district = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 3 AND pid = {$city_id}" . $orderBy);
        }

        //提交
        if ($_W['ispost']) {

            $newmoreinfo = $_GPC['newmoreinfo'];
            $subnewinfo = [];
            foreach ($moreinfo as $mminfo){
                $subnewinfo[$mminfo['key']] = $mminfo;
            }

            foreach ($subnewinfo as $subkey => &$new){
                if($new['id'] == 'datetime'){
                    $new['data'] = [];
                    $new['data'][0] = $newmoreinfo[$subkey]['start'];
                    $new['data'][1] = $newmoreinfo[$subkey]['end'];
                }else if($new['id'] == 'city'){
                    $new['data'] = [];
                    $new['data'][0] = pdo_getcolumn(PDO_NAME.'area',array('id'=>$newmoreinfo[$subkey]['provinceid']),'name');
                    $new['data'][1] = pdo_getcolumn(PDO_NAME.'area',array('id'=>$newmoreinfo[$subkey]['areaid']),'name');
                    $new['data'][2] = pdo_getcolumn(PDO_NAME.'area',array('id'=>$newmoreinfo[$subkey]['distid']),'name');
                }else{
                    $new['data'] = $newmoreinfo[$subkey];
                }
                if(empty($new['data'])){
                    unset($subnewinfo[$subkey]);
                }
            }

            $subnewinfo = array_values($subnewinfo);
            $subnewinfo = serialize($subnewinfo);
            $data['listinfo'] = $subnewinfo;
            $data['dotime'] = time();

            $res = pdo_update('wlmerchant_diyform_list',$data,array('id' => $id));
            if($res){
                wl_message('保存成功！',web_url('agentset/diyForm/record',['id' => $info['formid'] ]),'success');
            }else{
                wl_message('保存失败，请刷新重试');
            }
        }


        include wl_template('agentset/diy_form/recordedit');
    }

}
