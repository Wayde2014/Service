<?php
namespace app\data\controller;

use base\Base;
use \app\data\model\UserModel;
use \app\data\model\AccountModel;
use third\Sms;

class User extends Base
{
    private $paytype_list = array(1001,1002); //1001-充值余额,1002-充值押金
    private $paychannel_list = array(1001,1002); //1001-支付宝充值,1002-微信充值
    private $paysuc = 100;
    private $payfail = -100;

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
        $Sms = new Sms();
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
        $Sms = new Sms();
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
     * 下用户充值订单接口
     * @return \think\response\Json
     */
    public function recharge(){
        //获取参数
        $ck = input('ck');
        $paytype = intval(input('paytype',0));
        $paymoney = floatval(input('paymoney',0));
        $paychannel = intval(input('channel',0));
        $payaccount = input('account');
        $paynote = input('paynote');

        //校验参数
        if(empty($ck)){
            $this->res['code'] = -1;
            $this->res['msg'] = 'CK不能为空';
            return json($this->res);
        }
        if(!in_array($paytype,$this->paytype_list)){
            $this->res['code'] = -1;
            $this->res['msg'] = '充值类型错误';
            return json($this->res);
        }
        if(!in_array($paychannel,$this->paychannel_list)){
            $this->res['code'] = -1;
            $this->res['msg'] = '充值渠道错误';
            return json($this->res);
        }
        if($paymoney <= 0){
            $this->res['code'] = -1;
            $this->res['msg'] = '充值金额不能小于0';
            return json($this->res);
        }

        //获取用户信息
        $userinfo = self::getUserInfo($ck);
        if(empty($userinfo)){
            $this->res['code'] = -1;
            $this->res['msg'] = '用户尚未登录';
            return json($this->res);
        }

        $AccountModel = new AccountModel();
        $uid = $userinfo['uid'];
        $orderid = $AccountModel->addChargeInfo($uid,$paymoney,$paytype,$paychannel,$payaccount,$paynote);
        if($orderid === false){
            $this->res['code'] = -1;
            $this->res['msg'] = '充值下单失败';
            return json($this->res);
        }

        //暂时直接入账成功
        $ret = $AccountModel->updateRechargeStatus($orderid,$this->paysuc,$bankorderid,$bankmoney);
        if($ret){
            $this->res['code'] = 1;
            $this->res['msg'] = '充值入账成功';
            return json($this->res);
        }else{
            $this->res['code'] = -1;
            $this->res['msg'] = '充值入账失败';
            return json($this->res);
        }

        $this->res['code'] = 1;
        $this->res['msg'] = '充值下单成功';
        return json($this->res);
    }

}
