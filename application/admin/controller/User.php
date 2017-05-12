<?php
namespace app\admin\controller;

use base\Base;
use \app\admin\model\AdminUserModel;
use think\Request;

class User extends Base
{
    private $uid = -1;
    private $ck = '';
    private $model = null;

    /**
     * 控制器初始化
     */
    public function __construct(){
        parent::__construct();
        $this->uid = input('uid');
        $this->ck = input('ck');
        $this->model = new AdminUserModel();
        //检查用户是否登录
        $request = Request::instance();
        if(!in_array($request->action(),array('login'))){
            if(!self::checkAdminLogin($this->uid,$this->ck)){
                die(json_encode(self::erres("用户未登录，请先登录")));
            }
        }
    }
    /**
     * 检测用户名是否可用
     */
    public function checkUserName()
    {
        $username = input('username');
        if(!checkUserName($username)){
            return json(self::erres("用户名不符合规则"));
        }
        if(false !== $this->model->checkUserName($username)){
            return json(self::erres("用户名已存在"));
        }
        return json(self::sucres());
    }

    /**
     * 获取登录用户管理菜单
     */
    public function getMenuList(){

    }

    /**
     * 新增用户
     * @return \think\response\Json
     */
    public function addUser()
    {
        $username = input('username');
        $password = trim(input('password'));
        $realname = input('realname');

        //检测用户名是否可用
        if(false !== $this->model->checkUserName($username)){
            return json(self::erres("该用户名已被使用"));
        }

        //密码不能为空
        if (empty($password)) {
            return json(self::erres("密码不能为空"));
        }

        $uid = $this->model->addUser($username,$password,$realname);
        if ($uid === false) {
            return json(self::erres("新增用户失败"));
        }
        return json(self::sucres());
    }

    /**
     * 修改用户信息
     */
    public function updateUserInfo(){
        $userinfo = array(
            'username' => input('username'),
            'password' => input('password'),
            'realname' => input('realname'),
            'userstatus' => intval(input('userstatus',100)),
        );
        $ori_userinfo = $this->model->getUserInfoByUid($this->uid);

        //修改用户名时需检测新用户名是否可用
        if((!empty($userinfo['username']) && $userinfo['username'] != $ori_userinfo['username'])){
            //检测用户名是否可用
            if(false !== $this->model->checkUserName($userinfo['username'])){
                return json(self::erres("该用户名已被使用"));
            }
        }

        //修改密码时
        if((!empty($userinfo['password']) && $userinfo['password'] != $ori_userinfo['password'])){
            //密码不能为空
            if (empty($userinfo['password'])) {
                return json(self::erres("密码不能为空"));
            }
        }

        //更新
        if($this->model->updateUserInfo($this->uid,$userinfo)){
            return json(self::sucres());
        }else{
            return json(self::erres("修改用户信息失败"));
        }
    }

    /**
     * 删除用户信息(禁止删除自己)
     * 一并删除用户角色关联信息
     * 一并删除用户登录信息
     */
    public function delUser(){
        $uidlist = explode(',',trim(input('uidlist')));
        if(empty($uidlist)){
            return json(self::erres("待删除用户ID列表为空"));
        }
        if(in_array($this->uid,$uidlist)){
            return json(self::erres("不能删除自己"));
        }
        if($this->model->delUser($uidlist)){
            return json(self::sucres());
        }else{
            return json(self::erres("删除用户信息失败"));
        }
    }


    /**
     * 获取单个用户信息
     */
    public function getUserInfo()
    {
        //获取用户信息
        $userinfo = $this->model->getUserInfoByUid($this->uid);
        if(empty($userinfo)){
            return json(self::erres("用户信息不存在"));
        }

        $resinfo = $userinfo;
        return json(self::sucres($resinfo));
    }

    /**
     * 获取用户列表
     */
    public function getUserList(){

    }

    /**
     * 用户登录
     * @return \think\response\Json
     */
    public function login()
    {
        $username = trim(input('username'));
        $password = trim(input('password'));
        $ip = trim(input('ip'));

        if(empty($username) || empty($password)){
            return json(self::erres("用户名或密码为空"));
        }
        $ret_user = $this->model->checkUserName($username);
        if(false === $ret_user){
            return json(self::erres("用户名不存在"));
        }
        $this->uid = $ret_user['uid'];
        $userinfo = $this->model->getUserInfoByUid($this->uid);
        if(empty($userinfo)){
            return json(self::erres("用户ID不存在"));
        }

        if(strtoupper($password) !== $userinfo['password']){
            return json(self::erres("登录密码不正确"));
        }

        //写登录信息
        $ck = 'ck_' . strtoupper(base64_encode(md5($this->uid.$username.time())));
        $ret_login = $this->model->addUserLogin($ck,$this->uid,$ip);
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
        if ($this->model->setCkExpired($$this->ck)) {
            return json(self::sucres());
        } else {
            return json(self::erres("退出登录失败"));
        }
    }

    /**
     * 新增角色信息
     */
    public function addRole(){
        $rolename = trim(input('rolename'));
        $describle = trim(input('describle'));

        //角色名不能为空
        if (empty($rolename)) {
            return json(self::erres("角色名不能为空"));
        }

        //检测角色名是否可用
        if(!$this->model->checkRoleName($rolename)){
            return json(self::erres("该角色名已被使用"));
        }

        $rid = $this->model->addRole($rolename,$describle);
        if ($rid === false) {
            return json(self::erres("新增角色信息失败"));
        }
        return json(self::sucres());
    }

    /**
     * 修改角色信息
     */
    public function updateRoleInfo(){

    }

    /**
     * 删除角色信息
     */
    public function delRole(){

    }

    /**
     * 获取角色列表
     */
    public function getRoleList(){

    }

    /**
     * 获取单个角色信息
     * 1)角色基本信息
     * 2)使用该角色的用户信息列表
     * 3)该角色包含的模块信息列表
     */
    public function getRoleInfo(){

    }

    /**
     * 新增模块信息
     */
    public function addModule(){
        $modulename = trim(input('modulename'));
        $describle = trim(input('describle'));
        $moduletype = intval(input('moduletype',0));
        $xpath = trim(input('xpath'));
        $parentid = intval(input('parentid',0));
        $order = intval(input('order',0));

        //角色名不能为空
        if (empty($modulename)) {
            return json(self::erres("模块名称不能为空"));
        }

        $mid = $this->model->addModule($modulename,$describle,$moduletype,$xpath,$parentid,$order);
        if ($mid === false) {
            return json(self::erres("新增模块信息失败"));
        }
        return json(self::sucres());
    }

    /**
     * 修改模块信息
     */
    public function updateModuleInfo(){

    }

    /**
     * 删除模块信息
     * 一并删除模块角色关联信息
     */
    public function delModule(){
        $midlist = explode(',',trim(input('midlist')));
        if(empty($midlist)){
            return json(self::erres("待删除模块ID列表为空"));
        }
        if($this->model->delModule($midlist)){
            return json(self::sucres());
        }else{
            return json(self::erres("删除模块信息失败"));
        }
    }

    /**
     * 获取单个模块信息
     */
    public function getModuleInfo(){

    }

    /**
     * 获取模块列表
     */
    public function getModuleList(){

    }

}
