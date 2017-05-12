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
        $page = input('page',1); //页码
        $pagesize = input('pagesize',20); //每页显示数
        $ordertype = input('ordertype',1); //ordertype订单类型 1外卖订单 2食堂订单
        if(!$this->checkAdminLogin()){
            return json($this->erres("用户未登录，请先登录"));
        }
        $OrderModel = new OrderModel();
        if($ordertype == 1){
            $res = $OrderModel->getTakeoutlist($page, $pagesize);
        }else{
            $res = $OrderModel->getEatinlist($page, $pagesize);
        }
        if($res) {
            $list = $res;
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
            $info['num'] = $OrderModel->getOrderNum($ordertype);
        }
        return json($this->sucres($info, $list));
    }
    
    /**
     * 获取订单详情
     */
    public function getOrderinfo(){
        $info = array();
        $list = array();
        $orderid = input('orderid', 1); 
        $OrderModel = new OrderModel();
        $res = $OrderModel->getOrderinfo($orderid);
        if($res) {
            
        }
        return json($this->sucres($res, $list));
    }
}
