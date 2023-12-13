<?php
defined('IN_IA') or exit('Access Denied');
load()->classs('table');

class DistributionTable extends We7Table {

    protected $distributor_table = 'wlmerchant_distributor';

    public function getDisNumById($id, $level = 1) {
        if ($level == 1) {

        }
        $onenum = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_distributor') . " WHERE leadid = {$_W['wlmember']['id']}");
        $twonum = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_distributor') . " WHERE leadid in (select mid from " . tablename('wlmerchant_distributor') . " where `leadid` = {$_W['wlmember']['id']})");
        return $this->query->from($this->area_table)->where('id', $id)->get();
    }
}