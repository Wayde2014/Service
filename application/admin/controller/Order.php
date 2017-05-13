<?php
namespace app\admin\controller;

use base\Base;
use \app\admin\model\OrderModel;
use \app\admin\model\DishesModel;
use \app\admin\model\TastesModel;

class Order extends Base
{
    /**
     * 后台查询外卖订单
     * @return \think\response\Json
     */
    public function getOrderlist(){
        $info = array();
        $list = array();
        $startime = input('startime'); //起始时间
        $endtime = input('endtime'); //结束时间
        $shopname = input('shopname',''); //店铺名称
        $page = input('page',1); //页码
        $pagesize = input('pagesize',20); //每页显示数
        $ordertype = input('ordertype',1); //ordertype订单类型 1外卖订单 2食堂订单
        if($startime) $startime = Date('Y-m-d', strtotime($startime));
        else $startime = Date('Y-m-d');
        if($endtime) $endtime = Date('Y-m-d', strtotime($endtime));
        else $endtime = Date('Y-m-d');
        if(!$this->checkAdminLogin()){
            return json($this->erres("用户未登录，请先登录"));
        }
        $OrderModel = new OrderModel();
        if($ordertype == 1){
            $res = $OrderModel->getTakeoutlist($startime, $endtime, $shopname, $page, $pagesize);
        }else{
            $res = $OrderModel->getEatinlist($startime, $endtime, $shopname, $page, $pagesize);
        }
        $info['allnum'] = $res['allnum'];
        if($res['orderlist']) {
            $list = $res['orderlist'];
            $orderlist = array();
            $tastid = array();
            $dishid = array();
            foreach($list as $key=>$val){
                $orderdetail = $val['orderdetail'];
                preg_match_all('/(\d+)\|(\d+)\@(\d+)/i', $orderdetail, $match);
                if($match){
                    $orderlist = array_combine($match[1], $match[0]);
                    $dishid = array_merge($dishid, $match[1]);
                    $tastid = array_merge($tastid, $match[2]);
                }
                $list[$key]['orderlist'] = $orderlist;
            }
            $DishesModel = new DishesModel();
            $dishlist = $DishesModel->getDishesList(implode(',', array_unique($dishid)));
            $dishinfo = array();
            if($dishlist){
                foreach($dishlist as $key => $val){
                    $dishinfo[$val['id']] = $val;
                }
            }
            $TastesModel = new TastesModel();
            $tasteslist = $TastesModel->getDishesList(implode(',', array_unique($tastid)));
            $tastesinfo = array();
            if($tasteslist){
                foreach($tasteslist as $key => $val){
                    $tastesinfo[$val['tid']] = $val['tastes'];
                }
            }
            foreach($list as $key => $val){
                $orderlist = array();
                foreach($val['orderlist'] as $k => $v){
                    preg_match('/(\d+)\|(\d+)\@(\d+)/i', $v, $match);
                    $tastesid = $match[2];
                    $num = $match[3];
                    $orderinfo = isset($dishinfo[$k])?$dishinfo[$k]:array();
                    $orderinfo['num'] = $num;
                    $orderinfo['tastes'] = isset($tastesinfo[$tastesid])?$tastesinfo[$tastesid]:'';
                    array_push($orderlist, $orderinfo);
                }
                $list[$key]['orderlist'] = $orderlist;
            }
            
        }
        return json($this->sucres($info, $list));
    }
    
    /**
     * 订单派送
     */
    public function deliveryOrder(){
        $info = array();
        $list = array();
        $orderid = input('orderid');
        $distripid = input('distripid'); //配送员ID
        $OrderModel = new OrderModel();
        $info = $OrderModel->deliveryOrder($orderid, $distripid);
        return json($this->sucres($info));
    }
}
