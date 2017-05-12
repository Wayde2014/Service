<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 17-4-25
 * Time: 下午9:28
 */
namespace app\admin\model;

use think\Exception;
use think\Log;
use think\Model;
use think\Db;

class AdminUserModel extends Model
{
    //最大目录层级限制
    public $max_module_level = 5;

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
     * 删除用户信息
     * 一并删除用户角色关联信息
     * 一并删除用户登录信息
     * @param $uidlist
     * @return bool
     */
    public function delUser($uidlist){
        $table_user_info = 'admin_userinfo';
        $table_user_role = 'admin_user_role';
        $table_user_login = 'admin_login';
        Db::startTrans();
        try{
            //删除用户信息
            Db::name($table_user_info)->delete($uidlist);
            //删除用户角色关联信息
            Db::name($table_user_role)->where('f_uid','in',$uidlist)->delete();
            //删除用户登录信息
            Db::name($table_user_login)->where('f_uid','in',$uidlist)->delete();
            Db::commit();
            return true;
        }catch (Exception $e){
            Log::record($e);
            Db::rollback();
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

    /**
     * 检查角色名是否存在
     * @param $rolename
     * @return bool
     */
    public function checkRoleName($rolename)
    {
        $table_name = 'admin_role';
        $ret = Db::name($table_name)->field('f_rid as rid')->where('f_name', $rolename)->find();
        if (empty($ret)) {
            return false;
        }
        return true;
    }

    /**
     * 新增角色信息
     * @param $rolename
     * @param $describle
     * @return bool|int
     */
    public function addRole($rolename,$describle)
    {
        $table_name = 'admin_role';
        $data = array(
            'f_name' => $rolename,
            'f_describle' => $describle,
        );
        $roleId = intval(Db::name($table_name)->insertGetId($data));
        if ($roleId <= 0) {
            return false;
        }
        return $roleId;
    }

    /**
     * 新增模块信息
     * @param $modulename
     * @param $describle
     * @param $moduletype
     * @param $xpath
     * @param $parentid
     * @param $levelinfo
     * @param $order
     * @return bool|int
     */
    public function addModule($modulename,$describle,$moduletype,$xpath,$parentid,$levelinfo,$order)
    {
        $table_name = 'admin_module';
        $data = array(
            'f_name' => $modulename,
            'f_describle' => $describle,
            'f_moduletype' => $moduletype,
            'f_xpath' => $xpath,
            'f_parentid' => $parentid,
            'f_levelinfo' => $levelinfo,
            'f_order' => $order,
        );
        $moduleId = intval(Db::name($table_name)->insertGetId($data));
        if ($moduleId <= 0) {
            return false;
        }
        return $moduleId;
    }

    /**
     * 删除模块信息
     * 一并删除模块角色关联信息
     * @param $midlist
     * @return bool
     */
    public function delModule($midlist){
        $table_module_info = 'admin_module';
        $table_role_module = 'admin_role_module';
        Db::startTrans();
        try{
            //删除模块信息
            Db::name($table_module_info)->delete($midlist);
            //删除模块角色关联信息
            Db::name($table_role_module)->where('f_mid','in',$midlist)->delete();
            Db::commit();
            return true;
        }catch (Exception $e){
            Log::record($e);
            Db::rollback();
            return false;
        }
    }

    /**
     * 获取模块基本信息
     * @param $mid
     * @return array|false|\PDOStatement|string|Model
     */
    public function getModuleInfo($mid){
        $table_name = 'admin_module';
        $moduleinfo = Db::name($table_name)
            ->where('f_mid',$mid)
            ->field('f_mid as mid')
            ->field('f_name as modulename')
            ->field('f_describle as describle')
            ->field('f_moduletype as moduletype')
            ->field('f_xpath as xpath')
            ->field('f_parentid as parentid')
            ->field('f_levelinfo as levelinfo')
            ->field('f_order as order')
            ->field('f_lasttime as lasttime')
            ->find();
        return $moduleinfo;
    }

    /**
     * 根据角色ID获取用户列表(默认查全部用户)
     */
    public function getUserList($rid){
        if($rid > 0){
            $sql = "select f_uid as uid,f_username as username,f_userstatus as userstatus from t_admin_userinfo where f_uid in (select f_uid from t_admin_user_role where f_rid = :rid group by f_uid) order by username";
            $args = array(
                'rid' => $rid,
            );
            $userlist = Db::query($sql,$args);
        }else{
            $sql = "select f_uid as uid,f_username as username,f_userstatus as userstatus from t_admin_userinfo order by username";
            $userlist = Db::query($sql);
        }
        return $userlist;
    }

    /**
     * 删除角色信息
     * 一并删除角色模块关联信息
     * @param $rid
     * @return bool
     */
    public function delRole($rid){
        $table_role_info = 'admin_role';
        $table_role_module = 'admin_role_module';
        Db::startTrans();
        try{
            //删除角色信息
            Db::name($table_role_info)->delete($rid);
            //删除角色模块关联信息
            Db::name($table_role_module)->where('f_rid','in',$rid)->delete();
            Db::commit();
            return true;
        }catch (Exception $e){
            Log::record($e);
            Db::rollback();
            return false;
        }
    }

    /**
     * 获取角色列表
     */
    public function getRoleList(){
        $table_name = 'admin_role';
        $rolelist = Db::name($table_name)
            ->field('f_mid as rid')
            ->field('f_name as rolename')
            ->field('f_describle as describle')
            ->field('f_lasttime as lasttime')
            ->field('f_order as order')
            ->order('rolename asc')
            ->select();
        return $rolelist;
    }

    /**
     * 获取模块列表
     */
    public function getModuleList(){
        $table_name = 'admin_module';
        $modulelist = Db::name($table_name)
            ->field('f_mid as mid')
            ->field('f_name as modulename')
            ->field('f_describle as describle')
            ->field('f_moduletype as moduletype')
            ->field('f_xpath as xpath')
            ->field('f_parentid as parentid')
            ->field('f_levelinfo as levelinfo')
            ->field('f_order as order')
            ->field('f_lasttime as lasttime')
            ->order('modulename asc')
            ->select();
        return $modulelist;
    }
}