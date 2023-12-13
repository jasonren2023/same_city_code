<?php
defined('IN_IA') or exit('Access Denied');

class RanklistModuleUniapp extends Uniapp {
	public function index() {
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		//是否存在该排行榜
		$rank = pdo_get(PDO_NAME . 'rank', array('uniacid' => $_W['uniacid'], 'id' => $id));
		if (empty($rank)) {
			$this->result(1, '排行榜不存在或已被删除');
		}
		//定义基本内容
        $getNum = $rank['number'];//排行榜数量
        $orderType = $rank['type'].'_'.$rank['orderby'];//排行榜样式 组合方式:数据类型_排序方式
        $data = '';
        //获取对应的排行榜的数据信息  0=用户排行榜   1=商户排行榜
        switch ($orderType) {
            case '0_0':
                $table = 'mc_members';
                $orderby = 'credit2 DESC';
                $text = '积分';
                $where = "uniacid = {$_W['uniacid']} AND credit2 > 0.01";
                $field = " uid,nickname as name,avatar as images,credit2 as info";
                break;
            case '0_1':
                $table = 'mc_members';
                $orderby = 'credit1 DESC';
                $text = '余额';
                $where = "uniacid = {$_W['uniacid']} AND credit1 > 0.01";
                $field = " uid,nickname as name,avatar as images,credit1  as info";
                break;
            case '0_2':
                $table = PDO_NAME.'member';
                $orderby = 'dealmoney DESC';
                $text = '消费金额';
                $where = "uniacid = {$_W['uniacid']} AND dealmoney > 0.01";
                $field = " id,uid,nickname as name,avatar as images,dealmoney as info";
                break;
            case '1_11':
                $table = PDO_NAME.'merchantdata';
                $orderby = 'pv DESC';
                $text = '人气';
                $where = " status = 2 AND enabled = 1 AND pv > 0.01";
                $field = " id,storename as name,logo as images,pv as info";
                break;
            case '1_12':
                $sql = "SELECT id as fid,logo images,storename as name,(SELECT COUNT(*) FROM "
                    .tablename(PDO_NAME.'order')
                    ." WHERE sid = fid AND status IN (1,2,3,4,9)) + (SELECT COUNT(*) FROM "
                    .tablename(PDO_NAME.'rush_order')
                    ." WHERE sid = fid AND status IN (1,2,3,4,9)) as info FROM "
                    .tablename(PDO_NAME."merchantdata")
                    ." WHERE status = 2 AND enabled = 1 ORDER BY info DESC";
                $data = pdo_fetchall($sql);
                $text = '订单数';
                break;
            case '1_13':
                $table = PDO_NAME.'merchantdata';
                $orderby = 'allmoney DESC';
                $text = '营业额';
                $where = " status = 2 AND enabled = 1 AND allmoney > 0.01";
                $field = " id,storename as name,logo as images,allmoney as info";
                break;
            default:
                $this->result(1, '排行榜设置错误,请联系管理员');
                break;
        }
        if(!$data){
            $data = pdo_fetchall("SELECT {$field} FROM ".tablename($table)." WHERE {$where} ORDER BY {$orderby} LIMIT {$getNum}");
        }
        if($rank['type'] == 1){
            foreach ($data as $k => &$v){
                $v['images'] = tomedia($v['images']);
                if($orderType == '1_12' && $v['info'] < 0.01){
                    unset($data[$k]);
                }
            }
        }

		$this->result(0, '', array('name'=>$rank['name'],'text' => $text, 'data' => $data));
	}
	
}




































