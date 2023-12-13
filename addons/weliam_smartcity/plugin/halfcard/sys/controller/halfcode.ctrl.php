<?php
defined('IN_IA') or exit('Access Denied');

class Halfcode_WeliamController {
    public function lists() {
        global $_W, $_GPC;
        $where = array('uniacid' => $_W['uniacid']);
        if ($_GPC['status']) {
            if ($_GPC['status'] == 1)
                $where['status'] = 1;
            if ($_GPC['status'] == 2)
                $where['status'] = 0;
        }
        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1 :
                        $where['days'] = $_GPC['keyword'];
                        break;
                    case 2 :
                        $member = pdo_fetchall("SELECT id,nickname FROM ".tablename(PDO_NAME."member")
                                               ." WHERE nickname LIKE '%{$_GPC['keyword']}%' ");
                        $ids = array_column($member,'id');
                        if(is_array($ids) && count($ids) > 1){
                            $idStr = implode(',',$ids);
                            $where['#mid'] = "({$idStr})";
                        }else{
                            $where['mid'] = $ids[0];
                        }
                        break;
                    case 3 :
                        $where['@remark@'] = $_GPC['keyword'];
                        break;
                    case 4 :
                        $where['@number@'] = $_GPC['keyword'];
                        break;
                    default :
                        break;
                }
            }
        }

        //时间
        if (!empty($_GPC['time_limit'])) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where['createtime>'] = $starttime;
            $where['createtime<'] = $endtime;
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        if ($_GPC['export']) {
            $this->output($where);
        }

        $pindex = max(1, $_GPC['page']);
        $listData = Util::getNumData("*", PDO_NAME . 'token', $where, 'createtime desc', $pindex, 10, 1);
        $list = $listData[0];
        $pager = $listData[1];

        foreach ($list as $key => &$value) {
            if (!empty($value['aid'])) {
                $value['agentname'] = pdo_getcolumn(PDO_NAME . 'agentusers', array('uniacid' => $_W['uniacid'], 'id' => $value['aid']), 'agentname');
            } else {
                $value['agentname'] = '总平台';
            }
            if (!empty($value['mid'])) {
                $value['member'] = Member::wl_member_get($value['mid']);
            }
            if ($value['levelid']) {
                $value['levelname'] = pdo_getcolumn(PDO_NAME . 'halflevel', array('id' => $value['levelid']), 'name');
            } else {
                $value['levelname'] = $_W['wlsetting']['halflevel']['name'];
            }

            //电商联盟定制
            if (file_exists(PATH_MODULE . 'lsh.log')) {
                $value['caragentname'] = !empty($value['caraid']) ? pdo_getcolumn('weliam_shiftcar_agentusers', array('id' => $value['caraid']), 'agentname') : '总平台';
            }
        }
        include wl_template('halfcardsys/codelist');
    }

    public function add() {
        global $_W, $_GPC;
        $delevel = Setting::wlsetting_read('halflevel');
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
        $agents = pdo_getall('wlmerchant_agentusers', array('uniacid' => $_W['uniacid']), array('id', 'agentname'));

        //电商联盟定制
        if (file_exists(PATH_MODULE . 'lsh.log')) {
            $caragents = pdo_getall('weliam_shiftcar_agentusers', array('uniacid' => $_W['uniacid']), array('id', 'agentname'));
        }

        if (checksubmit()) {
            $num = !empty($_GPC['num']) ? intval($_GPC['num']) : 1;
            if ($num > 0) {
                for ($k = 0; $k < $num; $k++) {
                    $data['uniacid'] = $_W['uniacid'];
                    $data['caraid'] = intval($_GPC['caraid']);
                    $data['aid'] = intval($_GPC['aid']);
                    $data['days'] = intval($_GPC['days']) ? : 1;
                    $data['number'] = $_GPC['prefix'] . Util::createConcode(3);
                    $data['remark'] = $_GPC['remark'];
                    $data['levelid'] = $_GPC['levelid'];
                    $data['give_price'] = $_GPC['give_price'];
                    $data['createtime'] = TIMESTAMP;
                    pdo_insert(PDO_NAME . 'token', $data);
                }
                wl_message("创建成功!需要创建" . $num . "个，成功创建" . $k . "个。", web_url('halfcard/halfcode/lists'), 'success');
            } else {
                wl_message("数量填写不正确!", web_url('halfcard/halfcode/add'), 'error');
            }
        }
        include wl_template('halfcardsys/codeadd');
    }

    public function editcode() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $code = pdo_get('wlmerchant_token', array('id' => $id));
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
        $agents = pdo_getall('wlmerchant_agentusers', array('uniacid' => $_W['uniacid']), array('id', 'agentname'));

        //电商联盟定制
        if (file_exists(PATH_MODULE . 'lsh.log')) {
            $caragents = pdo_getall('weliam_shiftcar_agentusers', array('uniacid' => $_W['uniacid']), array('id', 'agentname'));
        }

        if ($_W['ispost']) {
            if ($id) {
                $data = array(
                    'days'       => intval($_GPC['days']),
                    'levelid'    => intval($_GPC['levelid']),
                    'remark'     => trim($_GPC['remark']),
                    'caraid'     => intval($_GPC['caraid']),
                    'aid'        => intval($_GPC['aid']),
                    'give_price' => trim($_GPC['give_price'])
                );
                if ($_GPC['range']) {
                    $res = pdo_update('wlmerchant_token', $data, array('remark' => $code['remark']));
                } else {
                    $res = pdo_update('wlmerchant_token', $data, array('id' => $id));
                }
            }
            if ($res) {
                show_json(1, '操作成功');
            } else {
                show_json(0, '操作失败,请重试');
            }
        }
        include wl_template('halfcardsys/codemodel');
    }

    public function remark() {
        global $_W, $_GPC;
        if ($_W['ispost']) {
            $remark = $_GPC['remark'];
            $id_num = $_GPC['id_num'];
            $id_nums = explode(',', $id_num);
            if ($id_nums[0] > $id_nums[1] || empty($id_nums[1])) {
                show_json(0, '激活码范围错误请重新填写');
            }

            for ($i = $id_nums[0]; $i <= $id_nums[1]; $i++) {
                pdo_update(PDO_NAME . 'token', array('remark' => $remark), array('uniacid' => $_W['uniacid'], 'id' => $i));
            }

            show_json(1, '操作成功');
        }
        include wl_template('halfcardsys/coderemark');
    }

    public function delcode() {
        global $_W, $_GPC;
        $res =  pdo_delete(PDO_NAME . 'token', array('id' => $_GPC['id']));
        if($res){
            show_json(1,'操作成功');
        }else {
            show_json(0,'网络错误，请刷新重试!');
        }
    }

    public function output($where) {
        global $_W, $_GPC;
        $listData = Util::getNumData("*", PDO_NAME . 'token', $where, 'createtime desc', 1, 40000, 1);
        $list = $listData[0];
        foreach ($list as $key => &$value) {
            if (!empty($value['mid'])) {
                $member = Member::wl_member_get($value['mid']);
                $value['nickname'] = $member['nickname'];
            } else {
                $value['nickname'] = '';
            }
            if (!empty($value['aid'])) {
                $value['agentname'] = pdo_getcolumn(PDO_NAME . 'agentusers', array('uniacid' => $_W['uniacid'], 'id' => $value['aid']), 'agentname');
            } else {
                $value['agentname'] = '总平台';
            }
            if ($value['levelid']) {
                $value['levelname'] = pdo_getcolumn(PDO_NAME . 'halflevel', array('id' => $value['levelid']), 'name');
            } else {
                $value['levelname'] = $_W['wlsetting']['halflevel']['name'];
            }
        }

        /* 输出表头 */
        $filter = array(
            'id'         => '激活码ID',
            'agentname'  => '所属代理',
            'number'     => '激活码',
            'days'       => '时长(天)',
            'levelname'  => '激活等级',
            'remark'     => '备注',
            'nickname'   => '使用人昵称',
            'createtime' => '生成时间',
        );

        $data = array();
        for ($i = 0; $i < count($list); $i++) {
            foreach ($filter as $key => $title) {
                if ($key == 'createtime') {
                    $data[$i][$key] = date('Y-m-d H:i:s', $list[$i][$key]);
                } else {
                    $data[$i][$key] = $list[$i][$key];
                }
            }
        }

        util_csv::export_csv_2($data, $filter, '激活码列表.csv');
        exit();
    }

    //删除
    function deletejihuoqr() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $ids = $_GPC['ids'];
        if ($id) {
            $res = pdo_delete('wlmerchant_token', array('id' => $id));
            if ($res) {
                die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
            } else {
                die(json_encode(array('errno' => 2, 'message' => $res, 'id' => $id)));
            }
        }
        if ($ids) {
            foreach ($ids as $key => $id) {
                pdo_delete('wlmerchant_token', array('id' => $id));
            }
            die(json_encode(array('errno' => 0, 'message' => '', 'id' => '')));
        }
    }


    //导入一卡通
    public function csv_add() {
        global $_W;
        //1.
        $filename = $_FILES['csv_file']['name'];
        $filename = substr($filename, -4, 4);
        if (empty ($filename)) {
            wl_message("请选择要导入的CSV文件", web_url('halfcard/halfcode/add'), 'success');
            exit;
        }
        if ($filename !== '.csv') {
            wl_message("请选择CSV文件", web_url('halfcard/halfcode/add'), 'success');
            exit;
        }
        //2.
        $file_path = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file_path, 'r');
        $file_size = filesize($file_path);    //文件大小
        if ($file_size == 0) {
            wl_message("没有任何数据", web_url('halfcard/halfcode/add'), 'success');
            exit;
        }
        $sql = 'INSERT INTO  ' . tablename('wlmerchant_token') . ' (id,number,uniacid,aid,days,	
	price,type,tokentype,typename,status,remark,openid,mid,createtime,levelid,give_price,caraid) values';
        $result = fgetcsv($handle);             //解析csv 获取一行作为数组 指针指向下一行  第一行是头
        while ($result = fgetcsv($handle)) {
            $sql .= '(' . '\'\''                                                    //id
                . ',\'' . $result[1] . '\','                                    //激活码
                . '\'' . $_W['uniacid'] . '\','                                //uniacid
                . '\'' . $_W['aid'] . '\','                                        //aid
                . '\'' . $result[2] . '\','                                     //days
                . '\'' . null . '\','                                            //price
                . '\'' . null . '\','                                            //type
                . '\'' . null . '\','                                            //tokentype
                . '\'' . null . '\','                                            //typename
                . '\'' . null . '\','                                            //status
                . '\'' . iconv('gb2312', 'utf-8', $result[4]) . '\','              //remark
                . '\'' . null . '\','                                            //openid
                . '\'' . null . '\','                                            //mid
                . '\'' . time() . '\','                                        //createtime
                . '\'' . $result[3] . '\','                                     //levelid
                . '\'' . null . '\','                                            //give_price
                . '\'' . null . '\' ),';                                            //caraid
        }
        fclose($handle);
        pdo_query(substr($sql, 0, strlen($sql) - 1)) or die('导入失败');
        wl_message("导入csv成功", web_url('halfcard/halfcode/add'), 'success');

    }


}
