<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
namespace We7\Table\Basic;

class Reply extends \We7Table {
    protected $tableName = 'basic_reply';
    protected $primaryKey = 'id';
    protected $field = array(
        'rid',
        'content'
    );
    protected $default = array(
        'rid' => '',
        'content' => ''
    );
}