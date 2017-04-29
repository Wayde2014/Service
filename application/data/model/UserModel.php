<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 17-4-25
 * Time: 下午9:28
 */
namespace app\data\model;

use think\Model;
use think\Db;

class UserModel extends Model
{

    /**
     * 检查该手机号码是否已注册
     * @param $mobile
     * @return bool
     */
    public function checkMobile($mobile)
    {
        $table_name = 'user_info';
        $ret = Db::name($table_name)->field('f_uid as uid')->where('f_mobile', $mobile)->select();
        if (empty($ret)) {
            return false;
        }
        return $ret[0]['uid'];
    }

    /**
     * 新增用户
     * @param $mobile
     * @param $lastdevice
     * @return bool|int
     */
    public function addUser($mobile, $lastdevice)
    {
        $table_name = 'user_info';
        $data = array(
            'f_mobile' => $mobile,
            'f_lastdevice' => $lastdevice,
            'f_regtime' => date("Y-m-d H:i:s"),
        );
        $userId = intval(Db::name($table_name)->insertGetId($data));
        if ($userId <= 0) {
            return false;
        }
        return $userId;
    }

    /**
     * 检查并记录短信发送日志
     * @param $uid
     * @param $mobile
     * @return bool
     */
    public function checkSmslog($uid, $mobile)
    {
        $table_name = 'user_smslog';
        $ret = Db::name($table_name)->where('f_uid', $uid)
            ->where('f_mobile', $mobile)
            ->field('f_lasttime as lasttime')
            ->field('now() as curtime')
            ->select();
        if (empty($ret)) {
            $data = array(
                'f_uid' => $uid,
                'f_mobile' => $mobile,
            );
            Db::name($table_name)->insert($data);
        } else {
            $lasttime = $ret[0]['lasttime'];
            $curtime = $ret[0]['curtime'];
            if(strtotime($curtime)-strtotime($lasttime) <= 60){
                return false;
            }
        }
        return true;
    }

    /**
     * 更新短信发送日志
     * @param $uid
     * @param $mobile
     * @return bool
     */
    public function updateSmslog($uid, $mobile)
    {
        $table_name = 'user_smslog';
        $ret = Db::name($table_name)
            ->where('f_uid', $uid)
            ->where('f_mobile', $mobile)
            ->setInc('f_count');
        if(intval($ret) > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 延长登录过期时间
     * @param $usercheck
     * @return bool
     */
    public function extendExpireTime($usercheck){
        $table_name = 'user_login';
        $data = array(
            'f_expiretime' => date("Y-m-d H:i:s", time()+30*24*3600),
        );
        $retup = Db::name($table_name)
            ->where('f_usercheck',$usercheck)
            ->where('f_lasttime','< time',time()-3600)
            ->update($data);
        if($retup !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 设置CK过期
     * @param $usercheck
     * @return bool
     */
    public function setCkExpired($usercheck){
        $table_name = 'user_login';
        $data = array(
            'f_expiretime' => date("Y-m-d H:i:s", time()-60),
        );
        $retup = Db::name($table_name)
            ->where('f_usercheck',$usercheck)
            ->update($data);
        if($retup !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 记录登录信息
     * @param $ck
     * @param $uid
     * @param $deviceid
     * @param $platform
     * @param $ip
     * @param $remark
     * @return array
     */
    public function addUserLogin($ck, $uid, $deviceid, $platform, $ip, $remark){
        $table_name = 'user_login';
        //判断用户当前登录态是否已失效
        $ret = Db::name($table_name)
            ->where('f_uid', $uid)
            ->where('f_expiretime', '> time', time())
            ->field('f_usercheck as usercheck')
            ->select();

        if(!empty($ret)){
            $usercheck = $ret[0]['usercheck'];
            self::extendExpireTime($usercheck);
            return array(
                'usercheck' => $usercheck,
                'uid' => $uid,
            );
        }else{
            $expiretime = date("Y-m-d H:i:s", time()+30*24*3600);
            $data = array(
                'f_usercheck' => $ck,
                'f_uid' => $uid,
                'f_deviceid' => $deviceid,
                'f_platform' => $platform,
                'f_ip' => $ip,
                'f_remark' => $remark,
                'f_expiretime' => $expiretime,
            );
            Db::name($table_name)->insert($data);
            if(Db::name($table_name)->getLastInsID() <= 0){
                return false;
            }
            return array(
                'usercheck' => $ck,
                'uid' => $uid,
            );
        }
    }

    /**
     * 通过ck获取用户登录信息
     * @param $ck
     * @return bool
     */
    public function getLoginUserInfo($ck){
        $table_name = 'user_login';
        $userinfo = Db::name($table_name)
            ->where('f_usercheck',$ck)
            ->where('f_expiretime','GT',time())
            ->field('f_uid as uid')
            ->select();
        if(empty($userinfo)){
            return false;
        }
        return $userinfo[0];
    }

    /**
     * 新增充值订单信息
     * @param $uid
     * @param $paymoney
     * @param $paytype
     * @param $channel
     * @param $account
     * @param $paynote
     * @return bool|int
     */
    public function addChargeInfo($uid, $paymoney, $paytype, $channel, $account, $paynote){
        $table_name = 'user_charge_order';
        $data = array(
            'f_uid' => $uid,
            'f_paymoney' => $paymoney,
            'f_paytype' => $paytype,
            'f_channel' => $channel,
            'f_account' => $account,
            'f_paynote' => $paynote,
            'f_addtime' => date("Y-m-d H:i:s"),
        );
        $orderid = intval(Db::name($table_name)->insertGetId($data));
        if ($orderid <= 0) {
            return false;
        }
        return $orderid;
    }

    public function updateChargeStatus($orderid, $paymoney, $status){
        $table_name = 'user_charge_order';
        $data = array(
            'f_paymoney' => $paymoney,
            'f_status' => $status,
        );
        $retup = Db::name($table_name)
            ->where('f_id',$orderid)
            ->update($data);
        if($retup !== false){
            return true;
        }else{
            return false;
        }
    }
}