<?php
namespace app\admin\controller;
use \base\Base;
use think\Db;
use \app\admin\model\DineshopModel;

class Shop extends Base
{
    /**
     * 获取店铺对应的配送员信息
     */
    public function getDistripList(){
        $info = array();
        $list = array();
        $shopid = input('shopid');
        if(!$this->checkAdminLogin()){
            return json($this->erres("用户未登录，请先登录"));
        }
        $DineshopModel = new DineshopModel();
        $list = $DineshopModel->getDistripList($shopid);
        
        return json($this->sucres($info, $list));
    }
}