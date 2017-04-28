<?php
namespace base;

class Base
{
    // 配置参数
    private $sign_key = 'x$sfxF%Qu4';
    public $res = ["code" => "-1", "msg" => "", "info" => [], "list" => []];

    /**
     * 构造函数
     * @param array $res
     */
    function __construct($res = array())
    {
        if ($res && count($res) > 0) {
            foreach ($res as $key => $val) {
                $this->res[$key] = $val;
            }
        }
        if(!self::checkToken()){
            die(json_encode($this->res));
        }
    }

    /**
     * 验证签名
     * @return bool
     */
    public function checkToken(){
        //验证签名
        $sign_str = '';
        $token_ori = input('token','');
        if(empty($token_ori)){
            $this->res['code'] = -1;
            $this->res['msg'] = 'Token can not be empty';
            return false;
        }
        $params = input();
        if(!empty($params)){
            ksort($params);
            foreach($params as $k=>$v){
                if($k == 'token'){
                    continue;
                }
                $sign_str .= $k."=".$v."&";
            }
            if(!empty($sign_str)){
                $sign_str = substr($sign_str,0,-1);
            }
        }
        $token = strtoupper(md5($sign_str.$this->sign_key));
        if($token_ori != $token){
            $this->res['code'] = -1;
            $this->res['msg'] = 'Token error';
            return false;
        }
        return true;
    }
}

?>