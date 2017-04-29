<?php
namespace app\data\controller;

use base\Base;
use \app\data\model\UserModel;
use \app\data\model\AccountModel;
use third\Sms;

class User extends Base
{
    private $paytype_config = array(1001,1002); //1001-充值余额,1002-充值押金
    private $paychannel_config = array(1001,1002); //1001-支付宝充值,1002-微信充值
    private $drawtype_config = array(100,200); //100-余额提款,200-押金退款
    private $drawchannel_config = array(1001,1002); //1001-支付宝提款,1002-微信提款

    /**
     * 发生短信验证码接口
     * @return \think\response\Json
     */
    public function sendSms()
    {
        $mobile = input('mobile');
        //检查手机号码格式
        if (!check_mobile($mobile)) {
            $this->res['code'] = -1;
            $this->res['msg'] = '手机号码格式错误';
            return json($this->res);
        }

        $UserModel = new UserModel();

        //检查该手机号是否已注册，如无则注册
        $uid = $UserModel->checkMobile($mobile);
        if ($uid === false) {
            $uid = $UserModel->addUser($mobile);
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

        //设备号不能为空
        $last_deviceid = trim($deviceid);
        if (empty($last_deviceid)) {
            $this->res['code'] = -1;
            $this->res['msg'] = '设备号不能为空';
            return json($this->res);
        }

        //检查手机号有无注册
        $UserModel = new UserModel();
        $uid = $UserModel->checkMobile($mobile);
        if($uid === false){
            $this->res['code'] = -1;
            $this->res['msg'] = '用户不存在';
            return json($this->res);
        }

        //检查短信验证码是否正确
        $Sms = new Sms();
        $ret = $Sms->checksms($mobile, $vcode);
        if ($ret['code'] <= 0) {
            return json($ret);
        }

        //写登录信息
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
            'ck' => $ret_login['ck'],
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
     * 用户充值接口
     * @return \think\response\Json
     */
    public function recharge(){
        //获取参数
        $ck = input('ck');
        $paytype = intval(input('paytype',0));
        $paymoney = floatval(input('paymoney',0));
        $paychannel = intval(input('channel',0));

        //校验参数
        if(empty($ck)){
            $this->res['code'] = -1;
            $this->res['msg'] = 'CK不能为空';
            return json($this->res);
        }
        if(!in_array($paytype,$this->paytype_config)){
            $this->res['code'] = -1;
            $this->res['msg'] = '充值类型错误';
            return json($this->res);
        }
        if(!in_array($paychannel,$this->paychannel_config)){
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
        $orderid = $AccountModel->addChargeInfo($uid,$paymoney,$paytype,$paychannel);
        if($orderid === false){
            $this->res['code'] = -1;
            $this->res['msg'] = '充值下单失败';
            return json($this->res);
        }

        //测试--暂时直接入账成功
        $bankorderid = 9999;
        $bankmoney = $paymoney;
        $account = 'test';
        $paynote = 'test';
        $ret = $AccountModel->rechargeSuc($orderid, $bankorderid, $bankmoney, $account, $paynote);
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

    /**
     * 用户提款接口
     * @return \think\response\Json
     */
    public function draw(){
        //获取参数
        $ck = input('ck');
        $drawtype = intval(input('drawtype',200));
        $drawmoney = floatval(input('drawmoney',0));
        $drawchannel = intval(input('channel',0));

        //校验参数
        if(empty($ck)){
            $this->res['code'] = -1;
            $this->res['msg'] = 'CK不能为空';
            return json($this->res);
        }
        if(!in_array($drawtype,$this->drawtype_config)){
            $this->res['code'] = -1;
            $this->res['msg'] = '提款类型错误';
            return json($this->res);
        }
        if(!in_array($drawchannel,$this->drawchannel_config)){
            $this->res['code'] = -1;
            $this->res['msg'] = '提款渠道错误';
            return json($this->res);
        }
        if($drawmoney <= 0){
            $this->res['code'] = -1;
            $this->res['msg'] = '提款金额不能小于0';
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
        $orderid = $AccountModel->addChargeInfo($uid,$drawmoney,$drawtype,$drawchannel);
        if($orderid === false){
            $this->res['code'] = -1;
            $this->res['msg'] = '提款下单失败';
            return json($this->res);
        }

        //测试--暂时直接提款成功
        $bankorderid = 9999;
        $bankmoney = $drawmoney;
        $account = 'test';
        $drawnote = 'test';
        $ret = $AccountModel->rechargeSuc($orderid, $bankorderid, $bankmoney, $account, $drawnote);
        if($ret){
            $this->res['code'] = 1;
            $this->res['msg'] = '提款入账成功';
            return json($this->res);
        }else{
            $this->res['code'] = -1;
            $this->res['msg'] = '提款入账失败';
            return json($this->res);
        }

        $this->res['code'] = 1;
        $this->res['msg'] = '提款下单成功';
        return json($this->res);
    }

}
