<?php
namespace app\admin\controller;

use base\Base;
use \app\admin\model\UserModel;
use \app\admin\model\AccountModel;
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
        $ck = input('ck');
        $uid = input('uid');
        if(!self::checkLogin($uid,$ck)){
            return json(self::erres("用户未登录，请先登录"));
        }
        $UserModel = new UserModel();
        if ($UserModel->setCkExpired($ck)) {
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
        $uid = input('uid');
        if(empty($uid)) return json($this->erres('参数错误'));

        //判断用户登录
        if($this->checkLogin() === false) return json($this->erres('用户未登录，请先登录'));
        
        $UserModel = new UserModel();
        //检查该手机号是否已注册，如无则注册
        $res = $UserModel->getAddressList($uid);
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

        //判断用户登录
        if($this->checkLogin() === false) return json($this->erres('用户未登录，请先登录'));
        
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
     * 删除地址
     * @return \think\response\Json
     */
    public function delAddress()
    {
        $addressid = input('addressid');
        if(empty($addressid)) return json($this->erres('参数错误'));

        //判断用户登录
        if($this->checkLogin() === false) return json($this->erres('用户未登录，请先登录'));
        
        $UserModel = new UserModel();
        //检查该手机号是否已注册，如无则注册
        $info = $UserModel->delAddress($addressid);
        if($info) {
            return json($this->sucres());
        }else{
            return json($this->erres('删除地址失败'));
        }
    }

    /**
     * 设置默认地址
     * @return \think\response\Json
     */
    public function setDefAddress()
    {
        $uid = input('uid');
        if(empty($uid)) return json($this->erres('用户id为空'));
        $addressid = input('addressid');
        if(empty($addressid)) return json($this->erres('参数错误'));

        //判断用户登录
        if($this->checkLogin() === false) return json($this->erres('用户未登录，请先登录'));
        
        $UserModel = new UserModel();
        //检查该手机号是否已注册，如无则注册
        $info = $UserModel->setDefAddress($uid, $addressid);
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
        $uid = input('uid');
        if(empty($uid)) return json($this->erres('用户id为空'));
        $province = input('province');
        if(empty($province)) return json($this->erres('请传入省份地址'));
        $city = input('city');
        if(empty($city)) return json($this->erres('请传入省份地址'));
        $address = input('address');
        if(empty($address)) return json($this->erres('请传入详细地址'));
        $name = input('name');
        if(empty($name)) return json($this->erres('请传入收件人'));
        $mobile = input('mobile');
        if(empty($mobile)) return json($this->erres('请传入用户手机号'));
    
        //判断用户登录
        if($this->checkLogin() === false) return json($this->erres('用户未登录，请先登录'));
        
        $UserModel = new UserModel();
        //检查该手机号是否已注册，如无则注册
        $addressid = $UserModel->checkAddress($uid, $province, $city, $address);
        if ($addressid === false) {
            $addressid = $UserModel->addAddress($uid, $province, $city, $address, $name, $mobile);
            if ($addressid === false) {
                return json($this->erres('新增地址失败'));
            }else{
                $UserModel->setDefAddress($uid, $addressid);
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
        $uid = input('uid');
        if(empty($uid)) return json($this->erres('用户id为空'));
        $addressid = input('addressid');
        if(empty($addressid)) return json($this->erres('参数错误'));
        $province = input('province');
        $city = input('city');
        $address = input('address');
        $mobile = input('mobile');
        $name = input('name');
        if(empty($province)&&empty($city)&&empty($address)&&empty($name)&&empty($mobile)){
            return json($this->erres('请传入要修改的值'));
        }
        
        //判断用户登录
        if($this->checkLogin() === false) return json($this->erres('用户未登录，请先登录'));
        
        $params = array(
            "province" => $province,
            "city" => $city,
            "address" => $address,
            "name" => $name,
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
        $uid = input('uid');
        $paytype = intval(input('paytype',0));
        $paymoney = floatval(input('paymoney',0));
        $paychannel = intval(input('channel',0));

        //检查用户是否登录
        if(!self::checkLogin($uid,$ck)){
            return json(self::erres("用户未登录，请先登录"));
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

        //必须实名认证后方可充值
        $UserModel = new UserModel();
        if(!$UserModel->checkUserStatus($uid,'charge')){
            return json(self::erres("实名认证后方可充值"));
        }

        $AccountModel = new AccountModel();
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
        $uid = input('uid');
        $drawtype = intval(input('drawtype',200));
        $drawmoney = floatval(input('drawmoney',0));

        //检查用户是否登录
        if(!self::checkLogin($uid,$ck)){
            return json(self::erres("用户未登录，请先登录"));
        }

        //校验参数
        if(!in_array($drawtype,$this->drawtype_config)){
            return json(self::erres("提款类型错误"));
        }
        if($drawmoney <= 0){
            return json(self::erres("提款金额不能小于0"));
        }

        //获取用户信息
        $UserModel = new UserModel();
        $userinfo = $UserModel->getUserInfoByUid($uid);
        $usermoney = $userinfo['usermoney'];
        $depositmoney = $userinfo['depositmoney'];
        $freezemoney = $userinfo['freezemoney'];

        //清户(退押金)时,检查用户状态
        if($drawtype == 200){
            if(!$UserModel->checkUserStatus($uid,'draw')){
                return json(self::erres("用户状态异常,当前不能退押金"));
            }
            if($usermoney > 0){
                return json(self::erres("您账户还有余额未消费完"));
            }
            if($freezemoney > 0){
                return json(self::erres("您还有未完成交易"));
            }
            if($depositmoney < $drawmoney){
                return json(self::erres("押金余额不足"));
            }
        }
        if($drawtype == 100 && $usermoney < $drawmoney){
            return json(self::erres("账户余额不足"));
        }

        //冻结
        $AccountModel = new AccountModel();
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
        $uid = input('uid');

        //检查用户是否登录
        if(!self::checkLogin($uid,$ck)){
            return json(self::erres("用户未登录，请先登录"));
        }

        //获取用户信息
        $UserModel = new UserModel();
        $userinfo = $UserModel->getUserInfoByUid($uid);
        if(empty($userinfo)){
            return json(self::erres("用户信息不存在"));
        }

        $resinfo = $userinfo;
        return json(self::sucres($resinfo));
    }


    /**
     * 更新用户信息
     * @return \think\response\Json
     */
    public function updateUserInfo(){
        //获取参数
        $ck = input('ck');
        $uid = input('uid');
        $userinfo = array(
            'nickname' => input('nickname'),
            'mobile' => input('mobile'),
            'realname' => input('realname'),
            'sex' => intval(input('sex',0)),
            'idcard' => input('idcard'),
        );
        //检查参数
        if (!empty($userinfo['mobile']) && !check_mobile($userinfo['mobile'])) {
            return json(self::erres("手机号码格式错误"));
        }
        if (!empty($userinfo['sex']) && !in_array($userinfo['sex'],array(0,1,2))) {
            return json(self::erres("性别类型错误"));
        }
        if (!empty($userinfo['idcard']) && !check_idcode($userinfo['idcard'])) {
            return json(self::erres("身份证号码格式错误"));
        }

        //检查用户是否登录
        if(!self::checkLogin($uid,$ck)){
            return json(self::erres("用户未登录，请先登录"));
        }

        $UserModel = new UserModel();
        $ori_userinfo = $UserModel->getUserInfoByUid($uid);

        //用户已通过实名认证时,不允许更新真实姓名和身份证号码
        if((!empty($userinfo['realname']) && $userinfo['realname'] != $ori_userinfo['realname']) || (!empty($userinfo['idcard']) && $userinfo['idcard'] != $ori_userinfo['idcard'])){
            if($ori_userinfo['auth_status'] == 100){
                return json(self::erres("已通过实名认证,不允许修改真实姓名和身份证号码"));
            }
        }

        //检查该手机号是否已注册
        if(!empty($userinfo['mobile']) && $ori_userinfo['mobile'] != $userinfo['mobile']){
            if($UserModel->checkMobile($userinfo['mobile'])){
                return json(self::erres("该手机号码已被注册"));
            }
        }

        //更新
        if($UserModel->updateUserInfo($uid,$userinfo)){
            return json(self::sucres());
        }else{
            return json(self::erres("用户信息更新失败"));
        }
    }

    /**
     * 实名认证
     * @return \think\response\Json
     */
    public function auth(){
        //获取参数
        $ck = input('ck');
        $uid = input('uid');
        $realname = input('realname');
        $idcard = input('idcard');

        //检查用户是否登录
        if(!self::checkLogin($uid,$ck)){
            return json(self::erres("用户未登录，请先登录"));
        }

        //进行实名认证
        $auth = true;
        if(!$auth){
            return json(self::erres("实名认证失败"));
        }else{
            $UserModel = new UserModel();
            if(!$UserModel->updateUserInfo($uid,array('auth_status'=>100))){
                return json(self::erres("实名认证成功,更新用户信息失败"));
            }
        }
        return json(self::sucres());
    }

}
