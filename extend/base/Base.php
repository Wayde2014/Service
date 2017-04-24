<?php
namespace base;

class Base{
    // 配置参数
    public $res = ["code"=>"-1", "msg"=>"", "info"=>[], "list"=>[]];
    function __construct($res = array())
    {
        if($res && count($res) > 0){
            foreach($res as $key=>$val){
                $this->res[$key] = $val;
            }
        }
        return $this->res;
    }
}
?>