<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 17-4-25
 * Time: 下午9:28
 */
namespace app\admin\model;

use think\Model;
use think\Db;

class AdminUserModel extends Model
{

    /**
     * 检查该用户名是否已注册
     * @param $username
     * @return bool
     */
    public function checkUserName($username)
    {
        $table_name = 'admin_userinfo';
        $ret = Db::name($table_name)->field('f_uid as uid')->where('f_username', $username)->find();
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 新增用户
     * @param $username
     * @param $password
     * @param $realname
     * @return bool|int
     */
    public function addUser($username,$password,$realname)
    {
        $table_name = 'admin_userinfo';
        $data = array(
            'f_username' => $username,
            'f_password' => strtoupper(md5($password)),
            'f_realname' => $realname,
            'f_addtime' => date("Y-m-d H:i:s"),
        );
        $userId = intval(Db::name($table_name)->insertGetId($data));
        if ($userId <= 0) {
            return false;
        }
        return $userId;
    }

    /**
     * 延长登录过期时间
     * @param $ck
     * @return bool
     */
    public function extendExpireTime($ck){
        $table_name = 'admin_login';
        $data = array(
            'f_expiretime' => date("Y-m-d H:i:s", time()+1800),
        );
        $retup = Db::name($table_name)
            ->where('f_usercheck',$ck)
            ->where('f_lasttime','< time',time()-60)
            ->update($data);
        if($retup !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 设置CK过期
     * @param $ck
     * @return bool
     */
    public function setCkExpired($ck){
        $table_name = 'admin_login';
        $data = array(
            'f_expiretime' => date("Y-m-d H:i:s", time()-60),
        );
        $retup = Db::name($table_name)
            ->where('f_usercheck',$ck)
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
     * @param $ip
     * @return array|bool
     */
    public function addUserLogin($ck, $uid, $ip){
        $table_name = 'admin_login';
        //判断用户是否重复登录
        $userinfo = Db::name($table_name)
            ->where('f_uid',$uid)
            ->where('f_expiretime', '> time', time())
            ->field('f_uid as uid')
            ->field('f_usercheck as ck')
            ->order('f_expiretime desc')
            ->find();
        if(!empty($userinfo)){
            return array(
                'ck' => $userinfo['ck'],
                'uid' => $uid,
            );
        }

        $expiretime = date("Y-m-d H:i:s", time()+1800);
        $data = array(
            'f_usercheck' => $ck,
            'f_uid' => $uid,
            'f_ip' => $ip,
            'f_expiretime' => $expiretime,
        );
        Db::name($table_name)->insert($data);
        if(Db::name($table_name)->getLastInsID() <= 0){
            return false;
        }
        return array(
            'ck' => $ck,
            'uid' => $uid,
        );
    }

    /**
     * 通过ck获取用户登录信息
     * @param $ck
     * @param $uid
     * @return array|false|\PDOStatement|string|Model
     */
    public function getLoginUserInfo($ck,$uid){
        $table_name = 'admin_login';
        $userinfo = Db::name($table_name)
            ->where('f_uid',$uid)
            ->where('f_usercheck',$ck)
            ->where('f_expiretime', '> time', time())
            ->field('f_uid as uid')
            ->field('f_usercheck as ck')
            ->find();
        return $userinfo;
    }

    /**
     * 通过UID获取用户信息
     * @param $uid
     * @return array|false|\PDOStatement|string|Model
     */
    public function getUserInfoByUid($uid){
        $table_name = 'admin_userinfo';
        $userinfo = Db::name($table_name)
            ->where('f_uid',$uid)
            ->field('f_uid as uid')
            ->field('f_password as password')
            ->field('f_realname as realname')
            ->field('f_addtime as addtime')
            ->find();
        return $userinfo;
    }

    /**
     * 更新用户信息
     * @param $uid
     * @param $new_userinfo
     * @return bool
     */
    public function updateUserInfo($uid, $new_userinfo){
        $table_name = 'admin_userinfo';
        //获取更新前用户信息
        $ori_userinfo = self::getUserInfoByUid($uid);
        $userinfo = array(
            'f_password' => empty($new_userinfo['password']) ? $ori_userinfo['password'] : $new_userinfo['password'],
            'f_realname' => empty($new_userinfo['realname']) ? $ori_userinfo['realname'] : $new_userinfo['realname'],
            'f_userstatus' => empty($new_userinfo['userstatus']) ? $ori_userinfo['userstatus'] : $new_userinfo['userstatus'],
        );
        $retup = Db::name($table_name)
            ->where('f_uid',$uid)
            ->update($userinfo);
        if($retup !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除用户
     * @param $uid
     * @return bool
     */
    public function delUser($uid){
        $table_name = 'admin_userinfo';
        if(Db::name($table_name)->delete($uid) > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 检查用户状态(登录时检查)
     * @param $uid
     * @return bool
     */
    public function checkUserStatus($uid){
        $userinfo = self::getUserInfoByUid($uid);
        $userstatus = intval($userinfo['userstatus']);
        if($userstatus !== 100){
            return false;
        }else{
            return true;
        }
    }
}