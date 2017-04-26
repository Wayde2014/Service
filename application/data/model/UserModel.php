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
     * 记录登录信息
     * @param $ck
     * @param $uid
     * @param $deviceid
     * @param $platform
     * @param $ip
     * @param $remark
     * @param $expiretime
     * @return array
     */
    public function addUserLogin($ck, $uid, $deviceid, $platform, $ip, $remark, $expiretime){
        $table_name = 'user_login';
        //判断用户当前登录态是否已失效
        $ret = Db::name($table_name)
            ->where('f_uid', $uid)
            ->where('f_expiretime', '> time', time())
            ->field('f_usercheck as usercheck')
            ->select();

        if(!empty($ret)){
            //更新过期时间
            $usercheck = $ret[0]['usercheck'];
            $data = array(
                'f_expiretime' => $expiretime,
            );
            Db::name($table_name)->where('f_usercheck',$usercheck)->update($data);
            return array(
                'usercheck' => $usercheck,
                'expiretime' => $expiretime,
            );
        }else{
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
                'expiretime' => $expiretime,
            );
        }
    }
}