<?php
/**
 * Comment: 代理端头条信息管理
 * Author: ZZW
 * Date: 2018/8/30
 * Time: 14:32
 */

class Head_WeliamController {

    public function getCategory(){
        $table = tablename(PDO_NAME."headline_class");
        $info = pdo_fetchall("SELECT id,name FROM".$table);
        wl_json(1, '成功', $info);
    }

}








