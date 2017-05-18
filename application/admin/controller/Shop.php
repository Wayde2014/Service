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
            $dishlist = $DishesModel->getDishesList($menulist);
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
            $DishesModel = new DishesModel();
            $dishlist = $DishesModel->getDishesList($info['menulist']);
            if($dishlist){
                $disheslist = $dishlist;
            }
        }
        $info['disheslist'] = $disheslist;
        return json($this->sucjson($info, $list));
    }
    /**
     * 新增折扣时间段
     */
    public function addDiscountTimeslot(){
        $info = array();
        $list = array();
        $timeslot = input('timeslot'); //时间段
        $arr = explode('-', $timeslot);
        $startime = $arr[0];
        $endtime = $arr[1];
        if(!check_datetime($startime, 'hh:ii') || !check_datetime($endtime, 'hh:ii')) {
            return json($this->errjson(-20002)); exit;
        }
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $DineshopModel = new DineshopModel();
        $slotid = $DineshopModel->addDiscountTimeslot($startime,$endtime);
        if($slotid){
            return json($this->sucjson(array("slotid" => $slotid)));
        }else{
            return json($this->errjson(-1)); 
        }
    }
    /**
     * 删除折扣时间段
     */
    public function delDiscountTimeslot(){
        $info = array();
        $list = array();
        $slotid = input('slotid'); //时间段id
        if(empty($slotid)){
            return json($this->errjson(-20001));
        }
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $DineshopModel = new DineshopModel();
        $res = $DineshopModel->delDiscountTimeslot($slotid);
        if($res){
            return json($this->sucjson());
        }else{
            return json($this->errjson(-1)); 
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
        return json($this->sucjson($info, $list));
    }
    /**
     * 获取店铺折扣信息
     */
    public function getDineshopDiscount(){
        $info = array();
        $list = array();
        $shopid = input('shopid',1); //店铺ID
        if(empty($shopid)){
            return json($this->errjson(-20003));
        }
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $startdate = Date('Y-m-d');
        $endate = Date('Y-m-d', strtotime('+7 days'));
        $DineshopModel = new DineshopModel();
        $shopinfo = $DineshopModel->getDineshopInfo($shopid);
        $dishinfo = array();
        if($shopinfo){
            $info['shopid'] = $shopinfo['id'];
            $info['shopname'] = $shopinfo['shopname'];
            $info['shopicon'] = $shopinfo['shopicon'];
            $info['shopaddress'] = $shopinfo['address'];
            $DishesModel = new DishesModel();
            $dishlist = $DishesModel->getDishesList($shopinfo['menulist']);
            if($dishlist){
                foreach($dishlist as $key => $val){
                    $dishinfo[$val['id']] = $val['dishesname'];
                }
            }
        }
        $discountlist = $DineshopModel->getDineshopDiscount($shopid, $startdate, $endate);
        $discountimeslot = $DineshopModel->getDiscountTimeslot();
        foreach($discountimeslot as $key=>$val){
            $slotid = $val['id'];
            $timeslot = $val['timeslot'];
            $discid_list = array();
            $discount_list = array();
            foreach($discountlist as $k=>$v){
                if($v['timeslot'] == $timeslot){
                    $discid_list[$v['date']] = $v['id'];
                    $discount = array();
                    foreach(explode('$', $v['discount']) as $_k=>$_v){
                        preg_match('/(\d+)\|(\d+)\@(([1-9]\d*|0)(\.\d{1,2})?)/i', $_v, $match);
                        if($match[1]){
                            $discount[$_k]['dishid'] = $match[1];
                            $discount[$_k]['dishname'] = isset($dishinfo[$match[1]])?$dishinfo[$match[1]]:'';
                            $discount[$_k]['type'] = $match[2];
                            $discount[$_k]['num'] = $match[3]*10;
                        }
                    }
                    $discount_list[$v['date']] = $discount;
                }
            }
            $discountdata = array();
            for($i=0;$i<7;$i++){
                $date = Date('Y-m-d', strtotime('+'.$i.' days'));
                $discountdata[] = array(
                    'date' => $date,
                    'discid' => isset($discid_list[$date])?$discid_list[$date]:'',
                    'discount' => isset($discount_list[$date])?$discount_list[$date]:array()
                );
            }
            $list[$key]['slotid'] = $slotid;
            $list[$key]['timeslot'] = $timeslot;
            $list[$key]['discountdata'] = $discountdata;
        }
        return json($this->sucjson($info, $list));
    }
    /**
     * 添加店铺折扣信息
     */
    public function addDineshopDiscount(){
        $info = array();
        $list = array();
        $shopid = input('shopid'); //折扣信息
        if(empty($shopid)){
            return json($this->errjson(-20003));
        }
        $date = input('date'); //折扣日期
        if(empty($date)){
            return json($this->errjson(-80001));
        }
        $slotid = input('slotid'); //折扣时间段
        if(empty($slotid)){
            return json($this->errjson(-80002));
        }
        $discount = input('discount'); //折扣信息
        if(empty($discount)){
            return json($this->errjson(-80003));
        }
        foreach(explode('$', $discount) as $key=>$val){
            if(!preg_match( '/^\d+\|\d+\@([1-9]\d*|0)(\.\d{1,2})?$/i' , $val, $result)){
                return json($this->errjson(-80004)); exit;
            }
            preg_match('/(\d+)\|(\d+)\@(([1-9]\d*|0)(\.\d{1,2})?)/i', $val, $match);
            if($match[2]==1 && floatval($match[3]) > 1) {
                return json($this->errjson(-80004)); exit;
            }
        }
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $DineshopModel = new DineshopModel();
        $discountinfo = $DineshopModel->getDiscount($shopid, $date, $slotid);
        //已有数据则修改
        if($discountinfo){
            $id = $discountinfo['id'];
            if(strstr($discountinfo['discount'], $discount)){
                $discount = $discountinfo['discount'];
            }else{
                $discount = $discountinfo['discount']."$".$discount;
            }
            $res = $DineshopModel->modDineshopDiscount($id, $discount);
        }else{
            $res = $DineshopModel->addDineshopDiscount($shopid, $date, $slotid, $discount);
        }
        if($res){
            return json($this->sucjson());
        }else{
            return json($this->errjson(-1)); 
        }
    }
    /**
     * 修改店铺折扣信息
     */
    public function modDineshopDiscount(){
        $id = input('id'); //折扣信息ID
        if(empty($id)){
            return json($this->errjson(-20001));
        }
        $discount = input('discount'); //折扣信息
        if(!empty($discount)){
            foreach(explode('$', $discount) as $key=>$val){
                if(!preg_match( '/^\d+\|\d+\@([1-9]\d*|0)(\.\d{1,2})?$/i' , $val)){
                    return json($this->errjson(-80004)); exit;
                }
                preg_match('/(\d+)\|(\d+)\@(([1-9]\d*|0)(\.\d{1,2})?)/i', $val, $match);
                if($match[2]==1 && floatval($match[3]) > 1) {
                    return json($this->errjson(-80004)); exit;
                }
            }
        }
        $DineshopModel = new DineshopModel();
        if(!empty($discount)){
            $res = $DineshopModel->modDineshopDiscount($id, $discount);
        }else{
            $res = $DineshopModel->delDineshopDiscount($id);
        }
        if($res){
            return json($this->sucjson());
        }else{
            return json($this->errjson(-1)); 
        }
    }
    /**
     * 删除店铺折扣信息
     */
    public function delDineshopDiscount(){
        $id = input('id'); //折扣信息ID
        if(empty($id)){
            return json($this->errjson(-20001));
        }
        $DineshopModel = new DineshopModel();
        $res = $DineshopModel->delDineshopDiscount($id);
        if($res){
            return json($this->sucjson());
        }else{
            return json($this->errjson(-1)); 
        }
    }
    /**
     * 添加店铺桌型
     */
    public function addDesk(){
        $info = array();
        $list = array();
        $shopid = input('shopid'); //店铺ID
        if(empty($shopid)) return json($this->errjson(-20003));
        $seatnum = input('seatnum'); //就餐人数
        $desknum = input('desknum'); //数量
        if(empty($seatnum) || empty($desknum)) {
            return json($this->errjson(-20001));
        }
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $DineshopModel = new DineshopModel();
        $deskid = $DineshopModel->addDesk($shopid, $seatnum, $desknum);
        return json($this->sucjson(array('deskid' => $deskid)));
    }
    /**
     * 修改店铺桌型
     */
    public function modDesk(){
        $info = array();
        $list = array();
        $deskid = input('deskid'); //桌型ID
        $seatnum = input('seatnum'); //就餐人数
        $desknum = input('desknum'); //数量
        if(empty($deskid) || empty($seatnum) || empty($desknum)) {
            return json($this->errjson(-20001));
        }     
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $DineshopModel = new DineshopModel();
        $info = $DineshopModel->modDesk($deskid,$seatnum,$desknum);
        if($info){
           return json($this->sucjson()); 
        }else{
           return json($this->errjson()); 
        }
    }
    /**
     * 删除店铺桌型
     */
    public function delDesk(){
        $info = array();
        $list = array();
        $deskid = input('deskid'); //桌型ID
        if(empty($deskid)){
            return json($this->errjson(-20001));
        }        
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $DineshopModel = new DineshopModel();
        $info = $DineshopModel->delDesk($deskid);
        if($info){
           return json($this->sucjson()); 
        }else{
           return json($this->errjson()); 
        }
    }
    /**
     * 获取店铺桌型
     */
    public function getDesklist(){
        $info = array();
        $list = array();
        $shopid = input('shopid'); //店铺ID
        if(empty($shopid)){
            return json($this->errjson(-20001));
        }        
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $DineshopModel = new DineshopModel();
        $shopinfo = $DineshopModel->getDineshopInfo($shopid);
        if($shopinfo){
            $info['shopid'] = $shopinfo['id'];
            $info['shopname'] = $shopinfo['shopname'];
            $info['shopicon'] = $shopinfo['shopicon'];
            $info['shopaddress'] = $shopinfo['address'];
        }
        $list = $DineshopModel->getDesklist($shopid);
        return json($this->sucjson($info, $list));
    }
    /**
     * 获取店铺桌型信息
     */
    public function getDeskinfo(){
        $info = array();
        $list = array();
        $deskid = input('deskid'); //店铺ID
        if(empty($deskid)){
            return json($this->errjson(-20001));
        }        
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $DineshopModel = new DineshopModel();
        $info = $DineshopModel->getDeskinfo($deskid);
        return json($this->sucjson($info, $list));
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
        
        return json($this->sucjson($info, $list));
    }
}