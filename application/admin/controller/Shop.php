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
        return json($this->sucjson($info, $list));
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
        return json($this->sucjson($info, $list));
    }
    /**
     * 添加折扣时间段
     */
    public function addDiscountTimeslot(){
        $info = array();
        $list = array();
        $startime = input('startime'); //起始时间
        $endtime = input('endtime'); //结束时间
        if(!check_datetime($startime, 'hh:ii') || !check_datetime($endtime, 'hh:ii')) {
            return json($this->errjson(-20002));
        }
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $DineshopModel = new DineshopModel();
        $slotid = $DineshopModel->addDiscountTimeslot($startime, $endtime);
        return json($this->sucres(array('slotid' => $slotid)));
    }
    /**
     * 删除折扣时间段
     */
    public function delDiscountTimeslot(){
        $info = array();
        $list = array();
        $slotid = input('slotid'); //时间段ID
        if(empty($slotid)){
            return json($this->errjson(-20001));
        }        
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $DineshopModel = new DineshopModel();
        $info = $DineshopModel->delDiscountTimeslot($slotid);
        if($info){
           return json($this->sucjson()); 
        }else{
           return json($this->errjson()); 
        }
        
    }
    /**
     * 获取折扣时间段
     */
    public function getDiscountTimeslot(){
        $info = array();
        $list = array();
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $DineshopModel = new DineshopModel();
        $list = $DineshopModel->getDiscountTimeslot();
        return json($this->sucres($info, $list));
    }
    /**
     * 获取店铺折扣信息
     */
    public function getDineshopDiscount(){
        $info = array();
        $list = array();
        $shopid = input('shopid',1); //店铺ID
        if(empty($shopid)){
            return json($this->errjson(-20001));
        }
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $startdate = Date('Y-m-d');
        $endate = Date('Y-m-d', strtotime('+7 days'));
        $DineshopModel = new DineshopModel();
        $shopinfo = $DineshopModel->getDineshopInfo($shopid);
        if($shopinfo){
            $info['shopid'] = $shopinfo['id'];
            $info['shopname'] = $shopinfo['shopname'];
            $info['shopicon'] = $shopinfo['shopicon'];
            $info['shopaddress'] = $shopinfo['address'];
        }
        $discountlist = $DineshopModel->getDineshopDiscount($shopid, $startdate, $endate);
        $discountimeslot = $DineshopModel->getDiscountTimeslot();
        foreach($discountimeslot as $key=>$val){
            $startime = $val['startime'];
            $endtime = $val['endtime'];
            $discountdata = array();
            for($i=0;$i<7;$i++){
                //$discountdata[Date('Y-m-d', strtotime('+'.$i.' days'))] = array();
                $discountdata[] = array(
                    'date' => Date('Y-m-d', strtotime('+'.$i.' days')),
                    'discount' => array()
                );
            }
            foreach($discountlist as $k=>$v){
                if($v['startime'] <= $startime && $v['endtime'] >= $endtime){
                    foreach($discountdata as $d => $data){
                        if($v['startdate'] <= $data['date'] && $v['endate'] >= $data['date']){
                            array_push($discountdata[$d]['discount'], array(
                                'id' => $v['id'],
                                'dishesid' => $v['dishesid'],
                                'dishesname' => $v['dishesname'],
                                'type' => $v['type'],
                                'disnum' => $v['disnum'],
                                'addtime' => $v['addtime'],
                            ));
                        }
                    }
                }
            }
            $list[$key]['timeslot'] = substr($startime,0,5).'-'.substr($endtime,0,5);
            $list[$key]['discountdata'] = $discountdata;
        }
        return json($this->sucjson($info, $list));
    }
    /**
     * 修改店铺折扣信息
     */
    public function getDineshopDiscount(){
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
        
        return json($this->sucjson($info, $list));
    }
}