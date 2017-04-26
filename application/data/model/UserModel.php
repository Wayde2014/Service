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
        $ret = Db::name('user_info')->field('f_uid as uid')->where('f_mobile', $mobile)->select();
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
        $data = array(
            'f_mobile' => $mobile,
            'f_lastdevice' => $lastdevice,
            'f_regtime' => date("Y-m-d H:i:s"),
        );
        $userId = intval(Db::name('user')->insertGetId($data));
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
        $ret = Db::name('user_smslog')->where('f_uid', $uid)
            ->where('f_mobile', $mobile)
            ->field('f_lasttime as lasttime')
            ->field('now() as curtime')
            ->select();
        if (empty($ret)) {
            $data = array(
                'f_uid' => $uid,
                'f_mobile' => $mobile,
            );
            Db::name('user_smslog')->insert($data);
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
        $ret = Db::name('user_smslog')
            ->where('f_uid', $uid)
            ->where('f_mobile', $mobile)
            ->setInc('f_count');
        if(intval($ret) > 0){
            return true;
        }else{
            return false;
        }
    }
}