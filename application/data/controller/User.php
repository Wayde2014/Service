<?php
namespace app\data\controller;

use base\Base;
use \app\data\model\UserModel;

class User extends Base
{
    /**
     * 发生短信验证码接口
     * @return \think\response\Json
     */
    public function sendSms()
    {
        $mobile = input('mobile');
        $deviceid = input('deviceid');
        //检查手机号码格式
        if (!check_mobile($mobile)) {
            $this->res['code'] = -1;
            $this->res['msg'] = '手机号码格式错误';
            return json($this->res);
        }

        //设备号不能为空
        $last_deviceid = trim($deviceid);
        if (empty($last_deviceid)) {
            $this->res['code'] = -1;
            $this->res['msg'] = '设备号不能为空';
            return json($this->res);
        }

        $UserModel = new UserModel();

        //检查该手机号是否已注册，如无则注册
        $uid = $UserModel->checkMobile($mobile);
        if ($uid === false) {
            $uid = $UserModel->addUser($mobile, $last_deviceid);
            if ($uid === false) {
                $this->res['code'] = -1;
                $this->res['msg'] = '注册用户失败';
                return json($this->res);
            }
        }

        //检查记录短信发送日志
        if (!$UserModel->checkSmslog($uid, $mobile)) {
            $this->res['code'] = -1;
            $this->res['msg'] = '短信发送太频繁了';
            return json($this->res);
        }

        //发送短信验证码，并更新短信发送日志
        $Sms = new \third\Sms();
        $ret = $Sms->sendsms($mobile);
        if ($ret['code'] > 0) {
            if (!$UserModel->updateSmslog($uid, $mobile)) {
                $this->res['code'] = -1;
                $this->res['msg'] = '更新短信发送日志失败';
                return json($this->res);
            }
        }
        return json($ret);
    }

    /**
     * 手机号码 + 短信验证码 登录接口
     * @return \think\response\Json
     */
    public function login()
    {
        $mobile = input('mobile');
        $vcode = input('vcode');
        $deviceid = input('deviceid');
        $platform = input('platform');
        $ip = input('ip');
        $remark = input('remark');
        //检查短信验证码是否正确
        $Sms = new \third\Sms();
        $ret = $Sms->checksms($mobile, $vcode);
        if ($ret['code'] <= 0) {
            return json($ret);
        }

        //写登录信息
        $UserModel = new UserModel();
        $uid = $UserModel->checkMobile($mobile);
        $ck = 'ck_' . strtoupper(base64_encode(md5($uid + $mobile + time())));
        $platform = intval($platform);
        $ret_login = $UserModel->addUserLogin($ck, $uid, $deviceid, $platform, $ip, $remark);
        if ($ret_login === false) {
            $this->res['code'] = -1;
            $this->res['msg'] = '写登录信息失败';
            return json($this->res);
        }
        $this->res['code'] = 1;
        $this->res['msg'] = '登录成功';
        $this->res['info'] = array(
            'ck' => $ret_login['usercheck'],
            'uid' => $ret_login['uid'],
        );
        return json($this->res);
    }

    /**
     * 退出登录
     * @return \think\response\Json
     */
    public function logout()
    {
        $usercheck = input('ck');
        $UserModel = new UserModel();
        if ($UserModel->setCkExpired($usercheck)) {
            $this->res['code'] = 1;
            $this->res['msg'] = '退出登录成功';
            return json($this->res);
        } else {
            $this->res['code'] = -1;
            $this->res['msg'] = '退出登录失败';
            return json($this->res);
        }
    }

}
