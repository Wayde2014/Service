<?php
namespace app\data\controller;

use base\Base;
use \app\data\model\User_info;

class User extends Base
{
    /**
     * 发生短信验证码接口
     * @param $mobile
     * @return string
     */
    public function sendSms($mobile, $deviceid){
        //检查手机号码格式
        if(!check_mobile($mobile)){
            $this->res['code'] = -1;
            $this->res['msg'] = '手机号码格式错误';
            return json($this->res);
        }

        //设备号不能为空
        $last_deviceid = trim($deviceid);
        if(empty($last_deviceid)){
            $this->res['code'] = -1;
            $this->res['msg'] = '设备号不能为空';
            return json($this->res);
        }

        //检查该手机号是否已注册，如无则注册
        $User_info = new User_info();
        if(!$User_info->checkMobile($mobile)){
            if(!$User_info->addUser($mobile,$last_deviceid)){
                $this->res['code'] = -1;
                $this->res['msg'] = '注册用户失败';
                return json($this->res);
            }
        }

        //发送短信验证码
        $Sms = new \third\Sms();
        return json($Sms->sendsms($mobile));
    }

    /**
     * 手机号码 + 短信验证码 登录接口
     * @param $mobile
     * @param $vcode
     * @return string
     */
    public function login($mobile, $vcode){
        //检查短信验证码是否正确
        $Sms = new \third\Sms();
        $ret = $Sms->checksms($mobile, $vcode);
        if($ret['code'] > 0){
            return json($ret);
        }
        //写登录信息


    }

}
