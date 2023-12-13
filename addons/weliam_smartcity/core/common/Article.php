<?php
class Article {
    public function __construct() {
        $table = tablename(PDO_NAME."headline_class");
        $info = pdo_fetchall("SELECT id,name FROM".$table);
        wl_json(1, '成功', $info);
    }
//    public function getCategory(){
//
//    }
}