<?php
defined('IN_IA') or exit('Access Denied');
load()->classs('table');

class AgentareaTable extends We7Table {

    protected $tableName = 'wlmerchant_oparea';

    public function getAreaList() {
        return $this->query->from($this->tableName)->orderby('sort', 'DESC')->getall();
    }

    public function selectFields($select) {
        $this->query->select($select);
        return $this;
    }

    public function searchWithLevel($level) {
        $this->query->where('level', $level);
        return $this;
    }

    public function searchWithHot() {
        $this->query->where('ishot', 1);
        return $this;
    }

    public function searchWithOpen() {
        $this->query->where('status', 1);
        return $this;
    }

    public function searchWithUniacid($uniacid) {
        $this->query->where('uniacid', $uniacid);
        return $this;
    }

}