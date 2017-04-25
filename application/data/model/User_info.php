<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 17-4-25
 * Time: 下午9:28
 */
namespace app\data\model;

use think\Model;

class User_info extends Model{

    /**
     * 检查该手机号码是否已注册
     * @param $mobile
     * @return bool
     */
    public function checkMobile($mobile){
        $ret = $this->where('f_mobile', $mobile)->select();
        if(empty($ret)){
            return false;
        }
        return true;
    }

    /**
     * 新增用户
     * @param $mobile
     * @param $lastdevice
     * @return bool
     */
    public function addUser($mobile, $lastdevice){
        $this->f_mobile       = $mobile;
        $this->f_lastdevice   = $lastdevice;
        $this->f_regtime      = 'null';
        if($this->save() > 0){
            return true;
        }else{
            return false;
        }
    }
}