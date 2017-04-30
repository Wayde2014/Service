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
    private $drawtype_config = array(200); //100-余额提款,200-押金退款
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
            return json(self::erres("手机号码格式错误"));
        }

        $UserModel = new UserModel();

        //检查该手机号是否已注册，如无则注册
        $uid = $UserModel->checkMobile($mobile);
        if ($uid === false) {
            $uid = $UserModel->addUser($mobile);
            if ($uid === false) {
                return json(self::erres("注册用户失败"));
            }
        }

        //检查记录短信发送日志
        if (!$UserModel->checkSmslog($uid, $mobile)) {
            return json(self::erres("短信发送太频繁了"));
        }

        //发送短信验证码，并更新短信发送日志
        $Sms = new Sms();
        $ret = $Sms->sendsms($mobile);
        if ($ret['code'] > 0) {
            if (!$UserModel->updateSmslog($uid, $mobile)) {
                return json(self::erres("更新短信发送日志失败"));
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
            return json(self::erres("设备号不能为空"));
        }

        //检查手机号有无注册
        $UserModel = new UserModel();
        $uid = $UserModel->checkMobile($mobile);
        if($uid === false){
            return json(self::erres("用户不存在"));
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
            return json(self::erres("写登录信息失败"));
        }
        $resinfo = array(
            'ck' => $ret_login['ck'],
            'uid' => $ret_login['uid'],
        );
        return json(self::sucres($resinfo));
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
            return json(self::sucres());
        } else {
            return json(self::erres("退出登录失败"));
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

        //检查用户是否登录
        $ckinfo = self::getUserInfoByCk($ck);
        if(empty($ckinfo)){
            $this->res['code'] = -1;
            $this->res['msg'] = '用户尚未登录';
            return json($this->res);
        }

        //校验参数
        if(empty($ck)){
            return json(self::erres("CK不能为空"));
        }
        if(!in_array($paytype,$this->paytype_config)){
            return json(self::erres("充值类型错误"));
        }
        if(!in_array($paychannel,$this->paychannel_config)){
            return json(self::erres("充值渠道错误"));
        }
        if($paymoney <= 0){
            return json(self::erres("充值金额不能小于0"));
        }

        $AccountModel = new AccountModel();
        $uid = $ckinfo['uid'];
        $orderid = $AccountModel->addRechargeOrderInfo($uid,$paymoney,$paytype,$paychannel);
        if($orderid === false){
            return json(self::erres("充值下单失败"));
        }

        //测试--暂时直接入账成功
        $bankorderid = 9999;
        $bankmoney = $paymoney;
        $account = 'test';
        $paynote = 'test';
        $ret = $AccountModel->rechargeSuc($orderid, $bankorderid, $bankmoney, $account, $paynote);
        if($ret){
            return json(self::sucres());
        }else{
            return json(self::erres("充值入账失败"));
        }

        return json(self::sucres());
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

        //检查用户是否登录
        $ckinfo = self::getUserInfoByCk($ck);
        if(empty($ckinfo)){
            $this->res['code'] = -1;
            $this->res['msg'] = '用户尚未登录';
            return json($this->res);
        }

        //校验参数
        if(empty($ck)){
            return json(self::erres("CK不能为空"));
        }
        if(!in_array($drawtype,$this->drawtype_config)){
            return json(self::erres("提款类型错误"));
        }
        if($drawmoney <= 0){
            return json(self::erres("提款金额不能小于0"));
        }

        //获取用户信息
        $AccountModel = new AccountModel();
        $uid = $ckinfo['uid'];
        $userinfo = $AccountModel->getUserInfoByUid($uid);
        $usermoney = $userinfo['usermoney'];
        $depositmoney = $userinfo['depositmoney'];
        if($drawtype == 200 && $depositmoney < $drawmoney){
            return json(self::erres("押金余额不足"));
        }
        if($drawtype == 100 && $usermoney < $drawmoney){
            return json(self::erres("账户余额不足"));
        }

        //冻结
        $tradenote = '用户提款冻结';
        $freeze = $AccountModel->freeze($uid,$drawmoney,2001,$tradenote);
        if(!$freeze){
            return json(self::erres("用户提款冻结失败"));
        }

        $orderid = $AccountModel->addDrawOrderInfo($uid,$drawmoney,$drawtype);
        if($orderid === false){
            return json(self::erres("提款发起失败"));
        }

        //测试--暂时直接提款成功
        $bankorderid = 9999;
        $bankmoney = $drawmoney;
        $account = 'test';
        $drawnote = 'test';
        $channel = $this->drawchannel_config[0];
        $ret = $AccountModel->drawSuc($orderid, $channel, $bankorderid, $bankmoney, $account, $drawnote);
        if($ret){
            return json(self::sucres());
        }else{
            return json(self::erres("提款扣款失败"));
        }

        return json(self::sucres());
    }

    /**
     * 获取登录用户信息
     */
    public function getUserInfo(){
        //获取参数
        $ck = input('ck');

        //检查用户是否登录
        $ckinfo = self::getUserInfoByCk($ck);
        if(empty($ckinfo)){
            $this->res['code'] = -1;
            $this->res['msg'] = '用户尚未登录';
            return json($this->res);
        }

        //获取用户信息
        $uid = $ckinfo['uid'];
        $AccountModel = new AccountModel();
        $userinfo = $AccountModel->getUserInfoByUid($uid);
        if(empty($userinfo)){
            return json(self::erres("用户信息不存在"));
        }

        $resinfo = $userinfo;
        return json(self::sucres($resinfo));
    }

}
