<?php
defined('IN_IA') or exit('Access Denied');

class Userlabel {

    static function addlabel($mid, $goodsid, $type) {
        global $_W;
        if ($type == 'rush') {
            $labels = pdo_getcolumn(PDO_NAME . 'rush_activity', array('id' => $goodsid), 'userlabel');
        } else if ($type == 'fightgroup') {
            $labels = pdo_getcolumn(PDO_NAME . 'fightgroup_goods', array('id' => $goodsid), 'userlabel');
        } else if ($type == 'coupon') {
            $labels = pdo_getcolumn(PDO_NAME . 'couponlist', array('id' => $goodsid), 'userlabel');
        }

        $labels = unserialize($labels);
        if ($labels) {
            foreach ($labels as $key => $label) {
                $record = pdo_get(PDO_NAME . 'userlabel_record', array('mid' => $mid, 'labelid' => $label), array('id', 'times'));
                if ($record) {
                    $data['times'] = $record['times'] + 1;
                    $data['dotime'] = time();
                    pdo_update(PDO_NAME . 'userlabel_record', $data, array('id' => $record['id']));
                } else {
                    $data2 = array(
                        'uniacid'    => $_W['uniacid'],
                        'aid'        => $_W['aid'],
                        'labelid'    => $label,
                        'mid'        => $mid,
                        'times'      => 1,
                        'createtime' => time(),
                        'dotime'     => time()
                    );
                    pdo_insert(PDO_NAME . 'userlabel_record', $data2);
                }
            }
        }
    }

}