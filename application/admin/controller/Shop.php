<?php
namespace app\admin\controller;
use \base\Base;
use think\Db;
use \app\admin\model\DineshopModel;
use \app\admin\model\DishesModel;

class Shop extends Base
{
    /**
     * 获取店铺信息列表
     */
    public function getDineshopList(){
        $info = array();
        $list = array();
        $page = input('page',1); //页码
        $pagesize = input('pagesize',20); //每页显示数
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $menulist = array();
        $DineshopModel = new DineshopModel();
        $res = $DineshopModel->getDineshopList($page, $pagesize);
        $info['allnum'] = $res['allnum'];
        if($res['dineshoplist']) {
            $list = $res['dineshoplist'];
            foreach($list as $key => $val){
                $menulist = array_merge($menulist, explode(',',$val['menulist']));
            }
            $menulist = array_unique($menulist);
            $DishesModel = new DishesModel();
            $dishlist = $DishesModel->getDishesList(implode(',', $menulist));
            $dishinfo = array();
            if($dishlist){
                foreach($dishlist as $key => $val){
                    $dishinfo[$val['id']] = $val;
                }
            }
            foreach($list as $key => $val){
                $disheslist = array();
                foreach(explode(',',$val['menulist']) as $k => $v){
                    if(isset($dishinfo[$v])){
                        array_push($disheslist, $dishinfo[$v]);
                    }
                }
                $list[$key]['disheslist'] = $disheslist;
            }
        }
        return json($this->sucres($info, $list));
    }
    /**
     * 获取店铺信息
     */
    public function getDineshopInfo(){
        $info = array();
        $list = array();
        $shopid = input('shopid',1); //店铺ID
        if(empty($shopid)){
            return json($this->errjson(-20001));
        }
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $disheslist = array();
        $DineshopModel = new DineshopModel();
        $info = $DineshopModel->getDineshopInfo($shopid);
        if(isset($info['menulist'])){
            $menulist = explode(',',$info['menulist']);
            $DishesModel = new DishesModel();
            $dishlist = $DishesModel->getDishesList(implode(',', $menulist));
            if($dishlist){
                $disheslist = $dishlist;
            }
        }
        $info['disheslist'] = $disheslist;
        return json($this->sucres($info, $list));
    }
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