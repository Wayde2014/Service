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
        $ret = Db::name($table_name)->field('f_uid as uid')->where('f_mobile', $mobile)->find();
        if (empty($ret)) {
            return false;
        }
        return $ret['uid'];
    }

    /**
     * 新增用户
     * @param $mobile
     * @return bool|int
     */
    public function addUser($mobile)
    {
        $table_name = 'user_info';
        $data = array(
            'f_mobile' => $mobile,
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
            ->find();
        if (empty($ret)) {
            $data = array(
                'f_uid' => $uid,
                'f_mobile' => $mobile,
            );
            Db::name($table_name)->insert($data);
        } else {
            $lasttime = $ret['lasttime'];
            $curtime = $ret['curtime'];
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
     * @param $ck
     * @return bool
     */
    public function extendExpireTime($ck){
        $table_name = 'user_login';
        $data = array(
            'f_expiretime' => date("Y-m-d H:i:s", time()+30*24*3600),
        );
        $retup = Db::name($table_name)
            ->where('f_usercheck',$ck)
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
     * @param $ck
     * @return bool
     */
    public function setCkExpired($ck){
        $table_name = 'user_login';
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
     * @param $deviceid
     * @param $platform
     * @param $ip
     * @param $remark
     * @return array
     */
    public function addUserLogin($ck, $uid, $deviceid, $platform, $ip, $remark){
        $table_name = 'user_login';
        //判断用户当前登录态是否已失效
        $userinfo = self::getLoginUserInfo($ck);
        if(!empty($userinfo)){
            $ck = $userinfo['ck'];
            self::extendExpireTime($ck);
            return array(
                'ck' => $ck,
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
                'ck' => $ck,
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
            ->where('f_expiretime', '> time', time())
            ->field('f_uid as uid')
            ->field('f_usercheck as ck')
            ->find();
        return $userinfo;
    }

    /**
     * 新增地址
     */
    public function addAddress($userid, $province, $city, $address, $mobile)
    {
        $table_name = 'user_address_info';
        $data = array(
            'f_uid' => $userid,
            'f_province' => $province,
            'f_city' => $city,
            'f_address' => $address,
            'f_mobile' => $mobile,
            'f_addtime' => date("Y-m-d H:i:s"),
        );
        $addressid = intval(Db::name($table_name)->insertGetId($data));
        if ($addressid <= 0) {
            return false;
        }
        return $addressid;
    }

    /**
     * 检测地址是否已经注册
     */
    public function checkAddress($userid, $province, $city, $address, $mobile)
    {
        $table_name = 'user_address_info';
        $checkaddress = Db::name($table_name)
            ->field('f_id id')
            ->where('f_uid', $userid)
            ->where('f_province', $province)
            ->where('f_city', $city)
            ->where('f_address', $address)
            ->select();
        if(empty($checkaddress)){
            return false;
        }else{
            return $checkaddress[0]["id"];
        }
    }

    /**
     * 更新地址
     */
    public function updateAddress($addressid, $params)
    {
        $table_name = 'user_address_info';
        $data = array();
        if($params['province']) $data['f_province'] = $params['province'];
        if($params['city']) $data['f_city'] = $params['city'];
        if($params['address']) $data['f_address'] = $params['address'];
        if($params['mobile']) $data['f_mobile'] = $params['mobile'];
        $ret = Db::name($table_name)
            ->where('f_id', $addressid)
            ->update($data);
        if($ret !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取某一条地址信息
     */
    public function getAddressInfo($addressid){
        $table_name = 'user_address_info';
        $address = Db::name($table_name)
            ->where('f_id', $addressid)
            ->field('f_id id,f_province province,f_city city,f_address address,f_mobile mobile,f_isactive isactive')
            ->order('f_addtime', 'desc')
            ->select();
        if(empty($address)){
            return false;
        }
        return $address[0];
    }
    /**
     * 删除地址信息
     */
    public function delAddress($addressid){
        $table_name = 'user_address_info';
        $res = Db::name($table_name)
            ->where('f_id', $addressid)
            ->delete();
        if($res !== false){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 设置默认地址
     */
    public function setDefAddress($userid, $addressid){
        $table_name = 'user_address_info';
        $ret = Db::name($table_name)
            ->where('f_uid', $userid)
            ->update(array( 'f_isactive' => 0 ));
        if($ret !== false){
            $ret = Db::name($table_name)
                ->where('f_id', $addressid)
                ->update(array( 'f_isactive' => 1 ));
            if($ret !== false){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    /**
     * 获取用户配送地址信息
     */
    public function getAddressList($userid){
        $table_name = 'user_address_info';
        $address = Db::name($table_name)
            ->where('f_uid', $userid)
            ->field('f_id id,f_province province,f_city city,f_address address,f_mobile mobile,f_isactive isactive')
            ->order('f_addtime', 'desc')
            ->select();
        //var_dump(Db::name($table_name)->getLastSql());
        if(empty($address)){
            return false;
        }
        return $address;
    }
}