<?php
defined('IN_IA') or exit('Access Denied');
load()->classs('table');

class AreaTable extends We7Table {

    protected $area_table = 'wlmerchant_area';
    protected $oparea_table = 'wlmerchant_oparea';

    public function getAreaList() {
        return $this->query->from($this->area_table)->getall();
    }

    public function getAreaById($id) {
        return $this->query->from($this->area_table)->where('id', $id)->get();
    }

    public function selectFields($select) {
        $this->query->select($select);
        return $this;
    }

    public function searchWithLevel($level) {
        $this->query->where('level', $level);
        return $this;
    }

    public function searchWithOpen() {
        $this->query->where('visible', 2);
        return $this;
    }

    public function searchWithUniacid($uniacid) {
        $value = !empty($uniacid) ? [0, $uniacid] : 0;
        $this->query->where('displayorder', $value);
        return $this;
    }

    public function searchWithKeyword($keyword) {
        if (!empty($keyword)) {
            $this->query->where('name LIKE', "%{$keyword}%")->whereor('pinyin LIKE', "%{$keyword}%");
            return $this;
        }
    }
}