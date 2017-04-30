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
    
    /**
     * 获取地址列表
     * @return \think\response\Json
     */
    public function getAddressList()
    {
        $userid = input('userid');
        if(empty($userid)) return json($this->erres('参数错误'));
        
        $UserModel = new UserModel();
        //检查该手机号是否已注册，如无则注册
        $res = $UserModel->getAddressList($userid);
        if($res) {
            return json($this->sucres(array("num"=>count($res)), $res));
        }else{
            return json($this->erres('获取用户地址列表失败'));
        }
    }
    
    /**
     * 获取地址信息
     * @return \think\response\Json
     */
    public function getAddressInfo()
    {
        $addressid = input('addressid');
        if(empty($addressid)) return json($this->erres('参数错误'));
        
        $UserModel = new UserModel();
        //检查该手机号是否已注册，如无则注册
        $info = $UserModel->getAddressInfo($addressid);
        if($info) {
            return json($this->sucres($info));
        }else{
            return json($this->erres('获取用户地址信息失败'));
        }
    }
    
    /**
     * 设置默认地址
     * @return \think\response\Json
     */
    public function setDefAddress()
    {
        $userid = input('userid');
        $addressid = input('addressid');
        if(empty($addressid)) return json($this->erres('参数错误'));
        
        $UserModel = new UserModel();
        //检查该手机号是否已注册，如无则注册
        $info = $UserModel->setDefAddress($userid, $addressid);
        if($info) {
            return json($this->sucres());
        }else{
            return json($this->erres('设置默认地址失败'));
        }
    }
    
    /**
     * 新增地址
     * @return \think\response\Json
     */
    public function addAddress()
    {
        $userid = input('userid');
        if(empty($userid)) return json($this->erres('用户ID为空'));
        $province = input('province');
        if(empty($province)) return json($this->erres('请传入省份地址'));
        $city = input('city');
        if(empty($city)) return json($this->erres('请传入省份地址'));
        $address = input('address');
        if(empty($address)) return json($this->erres('请传入详细地址'));
        $mobile = input('mobile');
        if(empty($mobile)) return json($this->erres('请传入用户手机号'));

        $UserModel = new UserModel();
        //检查该手机号是否已注册，如无则注册
        $addressid = $UserModel->checkAddress($userid, $province, $city, $address, $mobile);
        if ($addressid === false) {
            $addressid = $UserModel->addAddress($userid, $province, $city, $address, $mobile);
            if ($addressid === false) {
                return json($this->erres('新增地址失败'));
            }else{
                $UserModel->setDefAddress($userid, $addressid);
                return json($this->sucres(array("addressid" => $addressid)));
            }
        }else{
            return json($this->sucres(array("addressid" => $addressid)));
        }
    }
    
    /**
     * 修改地址
     * @return \think\response\Json
     */
    public function modAddress()
    {
        $addressid = input('addressid');
        if(empty($addressid)) return json($this->erres('参数错误'));
        $province = input('province');
        $city = input('city');
        $address = input('address');
        $mobile = input('mobile');
        if(empty($province)&&empty($city)&&empty($address)&&empty($mobile)){
            return json($this->erres('请传入要修改的值'));
        }
        $params = array(
            "province" => $province,
            "city" => $city,
            "address" => $address,
            "mobile" => $mobile,
        );
        $UserModel = new UserModel();
        //检查该手机号是否已注册，如无则注册
        $res = $UserModel->updateAddress($addressid, $params);
        if($res) {
            return json($this->sucres());
        }else{
            return json($this->erres('更新地址失败'));
        }
    }

}
