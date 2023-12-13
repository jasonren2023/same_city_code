<?php
defined('IN_IA') or exit('Access Denied');

class Wlsign {
//	更新用户签到数据	
    static function updateSignmember($update, $where = array()) {
        global $_W;
        $where['mid'] = $_W['mid'];
        $res = pdo_update(PDO_NAME . 'signmember', $update, $where);
        if ($res) {
            return 1;
        } else {
            return 0;
        }
    }

//	查询用户签到数据
    static function querySignmember($select, $where = array()) {
        global $_W;
        $where['mid'] = $_W['mid'];
        $member = Util::getSingelData($select, PDO_NAME . 'signmember', $where);
        if ($member) {
            $member['integral'] = intval($_W['wlmember']['credit1']);
            $user = pdo_get('wlmerchant_member', array('id' => $_W['mid']), array('avatar', 'nickname'));
            $member['nickname'] = $user['nickname'];
            $member['avatar'] = $user['avatar'];
            pdo_update(PDO_NAME . 'signmember', array('integral' => $member['integral'], 'nickname' => $member['nickname'], 'avatar' => $member['avatar']), array('id' => $member['id']));
            return $member;
        } else {
            if ($_W['mid']) {
                $user = pdo_get('wlmerchant_member', array('id' => $_W['mid']), array('avatar', 'nickname'));
                $member['uniacid'] = $_W['uniacid'];
                $member['mid'] = $_W['mid'];
                $member['times'] = 0;
                $member['totaltimes'] = 0;
                $member['integral'] = $_W['member']['credit1'];
                $member['createtime'] = time();
                $member['avatar'] = $user['avatar'];
                $member['nickname'] = $user['nickname'];
                pdo_insert(PDO_NAME . 'signmember', $member);
                return $member;
            }

        }
    }

//	查询单条用户签到记录
    static function queryRecord($select, $date) {
        global $_W;
        $where['mid'] = $_W['mid'];
        $where['date'] = $date;
        return Util::getSingelData($select, PDO_NAME . 'signrecord', $where);
    }

//	保存用户签到记录
    static function saveRecord($record, $param = array()) {
        global $_W;
        if (!is_array($record)) return FALSE;
        $record['uniacid'] = $_W['uniacid'];
        $record['mid'] = $_W['mid'];
        $record['createtime'] = time();
        if (empty($param)) {
            pdo_insert(PDO_NAME . 'signrecord', $record);
            return pdo_insertid();
        }
        return FALSE;
    }

//	获取用户记录排名
    static function getMemberlist() {
        global $_W;
        $goodsInfo = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_signmember') . "WHERE uniacid = {$_W['uniacid']} AND totaltimes > 0 ORDER BY times DESC limit 20");
        return $goodsInfo;
    }

//	查询单条用户领奖记录
    static function queryReceive($select, $date, $total) {
        global $_W;
        $where['mid'] = $_W['mid'];
        $where['date'] = $date;
        $where['total'] = $total;
        return Util::getSingelData($select, PDO_NAME . 'signreceive', $where);
    }

//	保存用户领取记录	
    static function saveReceive($receive, $param = array()) {
        global $_W;
        if (!is_array($receive)) return FALSE;
        $receive['uniacid'] = $_W['uniacid'];
        $receive['mid'] = $_W['mid'];
        $receive['createtime'] = time();
        if (empty($param)) {
            pdo_insert(PDO_NAME . 'signreceive', $receive);
            return pdo_insertid();
        }
        return FALSE;
    }

    //计划任务
    static function doTask() {
        global $_W;
        //签到周期信息处理
        $time = time();
        $begin = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $end = mktime(0, 3, 59, date('m'), 1, date('Y'));
        if ($time > $begin && $time < $end) {
            $settings = Setting::wlsetting_read('wlsign');
            $members = pdo_getall('wlmerchant_signmember', array('uniacid' => $_W['uniacid']));
            if ($members) {
                if ($settings['cycletype'] == 1) {
                    foreach ($members as $key => &$v) {
                        $v['record'] = '';
                        $res = pdo_update('wlmerchant_signmember', $v, array('id' => $v['id']));
                    }
                } else {
                    foreach ($members as $key => &$v) {
                        $v['record'] = '';
                        $v['totaltimes'] = '';
                        $v['total'] = '';
                        $res = pdo_update('wlmerchant_signmember', $v, array('id' => $v['id']));
                    }
                }
            }

        }
        //签到统计(排行) 信息处理
        //获取昨天签到了的所有用户的id
        $startTime = strtotime("-1 day",strtotime(date("Y-m-d 00:00:00")));//昨天开始时间
        $endTime =  strtotime(date("Y-m-d 00:00:00",time()));//今天开始时间
        //获取昨天签到用户id信息
        $where = " WHERE createtime > {$startTime} AND createtime < {$endTime}  ";
        $sql = " SELECT * FROM ".tablename(PDO_NAME."signrecord") .$where." GROUP BY mid ";
        $list = pdo_fetchall($sql);
        $mids = array_column($list,'mid');
        $updateWhere = " WHERE uniacid = {$_W['uniacid']} ";
        if(is_array($mids) && count($mids) > 0) $updateWhere .= ' AND mid NOT IN ('.implode(',',$mids).')';
        //修改信息
        pdo_fetch('UPDATE '.tablename(PDO_NAME."signmember")." set times = 0 ".$updateWhere);
    }

    /**
     * Comment: 获取签到总数
     * Author: zzw
     * Date: 2019/8/12 11:27
     */
    public static function getTotal($where = '') {
        global $_W;
        return pdo_fetchcolumn("SELECT count(*) FROM " . tablename(PDO_NAME . "signrecord")
            . " WHERE uniacid = {$_W['uniacid']} AND {$where}");
    }
    /**
     * Comment: 获取对应的签到排行榜信息
     * Author: zzw
     * Date: 2019/10/25 9:26
     * @param $field
     * @return mixed
     */
    public static function getRankingList($field) {
        global $_W;
        #1、基本条件获取
        $where = " WHERE uniacid = {$_W['uniacid']} ";
        #2、获取当前用户签到信息 建立用户默认排行信息,默认排行第一位
        $member = self::querySignmember('*');
        $user['nickname'] = $member['nickname'];//用户昵称
        $user['avatar'] = $member['avatar'];//用户头像
        $user['orders'] = 0;//用户排行
        $user['distance'] = 0;//对前一个用户的距离
        $user[$field] = $member[$field];//主要字段：用户连续签到次数/用户总签到次数/用户积分总数
        //获取排行榜信息列表
        $list = pdo_fetchall("SELECT @key:=@key+1 as `key`,nickname,avatar,{$field},mid FROM "
            . tablename(PDO_NAME . "signmember") . " ,(select @key:=0) as a "
            . $where . " AND {$field} > 0 AND 
            (SELECT id FROM ".tablename(PDO_NAME."member")." WHERE id = mid ) > 0
            ORDER BY {$field} DESC,id DESC LIMIT 10 ");
        //获取当前用户的排行 即距离前一名的差距
        if (is_array($list) && count($list) > 0) {
            $newList = array_column($list, 'key', 'mid');
            if ($newList[$_W['mid']]) {
                //当前用户存在排行榜中
                $keys = $newList[$_W['mid']] - 1;
                $user['orders'] = $keys + 1;
                //判断是否为第一名 做出操作
                if ($keys != 0) {
                    $user['distance'] = $list[$keys - 1][$field] - $user[$field];
                }
            } else {
                //当前用户未入排行榜
                $user['orders'] = '-1';
                $user['distance'] = $list[count($list) - 1][$field] - $user[$field];
            }
        }
        #3、数据拼装
        $data['list'] = $list;
        $data['user'] = $user;

        return $data;
    }


}
