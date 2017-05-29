<?php
namespace app\admin\controller;

use app\data\model\AccountModel;
use base\Base;
use \app\admin\model\OrderModel;
use \app\admin\model\DishesModel;
use \app\admin\model\TastesModel;
use \app\data\controller\User as FrontUser;
use third\Alipay;

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
            return json($this->errjson(-10001));
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
                if(isset($list[$key]['deliveryname']) && $list[$key]['deliveryname'] == null){
                    $list[$key]['deliveryname'] = '';
                }
                if(isset($list[$key]['deliverymobie']) && $list[$key]['deliverymobie'] == null){
                    $list[$key]['deliverymobie'] = '';
                }
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
            $tasteslist = $TastesModel->getTastesList(implode(',', array_unique($tastid)));
            $tastesinfo = array();
            if($tasteslist){
                foreach($tasteslist as $key => $val){
                    $tastesinfo[$val['id']] = $val['tastes'];
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
        return json($this->sucjson($info, $list));
    }
    /**
     * 订单处理
     */
    public function processOrder(){
        $info = array();
        $list = array();
        $orderid = input('orderid');
        $status = input('status');
        $data = array();
        if($status == 2){ //已付款处理
            $distripid = input('distripid'); //配送员ID;
            if(empty($distripid)) return json($this->erres("缺少参数"));
            $data['status'] = 3;
            $data['distripid'] = $distripid;
        }else if($status == 3){ //配送中处理
            $data['status'] = 4;
        }else if($status == 4) { //配送完成处理
            $data['status'] = 100;
        }
        if(count($data) == 0) return json($this->erres("参数错误"));
        $OrderModel = new OrderModel();
        $info = $OrderModel->processOrder($orderid, $data);
        if(!$info) return json($this->erres("更新失败"));
        return json($this->sucjson($info));
    }

    /**
     * 审核退款订单
     */
    public function checkupCancelOrder(){
        $uid = input('uid');
        $orderid = input('orderid');
        $checkupstatus = input('checkupstatus',1);    //审核结果 0-不通过，1-通过
        $OrderModel = new OrderModel();
        $orderinfo =$OrderModel->getOrderinfo($uid, $orderid);
        if(empty($orderinfo)){
            return json($this->erres("订单信息不存在"));
        }
        $order_status = $orderinfo['status'];
        $order_paytype = $orderinfo['paytype'];
        $order_paymoney = $orderinfo['paymoney'];
        if($order_status != $OrderModel->status_waiting_checkup_refund){
            return json($this->erres("订单非待审核退款状态"));
        }
        if($checkupstatus == 1){
            //审核通过
            if(!$OrderModel->updateTradeOrderInfo($uid,$orderid,$OrderModel->status_checkup_suc_refund)){
                return json(self::erres("退款订单审核失败"));
            }

            if($order_paytype == 0){
                //余额支付，撤单返款即完成
                //撤单返款
                $Account = new AccountModel();
                $tradetype = 1004;
                $deposit = $Account->deposit($uid,$order_paymoney,$tradetype,$orderid);
                if(!$deposit){
                    return json(self::erres("撤单返款失败"));
                }
                if($OrderModel->updateTradeOrderInfo($uid,$orderid,$OrderModel->status_refund_suc)){
                    return json(self::sucjson());
                }
            }else{
                //支付宝支付or微信支付
                //检查订单对应充值信息
                $AccountModel = new AccountModel();
                $rechargeinfo = $AccountModel->getTradeOrderRechargeInfo($uid,$orderid);
                if(empty($rechargeinfo)){
                    return json(self::erres("查不到该交易订单对应充值信息"));
                }
                $paystatus = $rechargeinfo['status'];
                $paymoney = $rechargeinfo['paymoney'];
                if($paystatus != $AccountModel->paysuc){
                    return json(self::erres("该交易订单未充值成功"));
                }
                if($order_paymoney > $paymoney){
                    return json(self::erres("退款金额不能超过该订单充值金额"));
                }
                $payorderid = $rechargeinfo['orderid'];
                $paybankorderid = $rechargeinfo['bankorderid'];
                $paychannel = $rechargeinfo['channel'];

                //检查当前充值渠道是否可退
                $FrontUser = new FrontUser();
                if(!in_array($paychannel,$FrontUser->allow_drawtype)){
                    return json(self::erres("该支付订单当前不支持原路退回"));
                }

                //撤单返款
                $Account = new AccountModel();
                $tradetype = 1004;
                $deposit = $Account->deposit($uid,$order_paymoney,$tradetype,$orderid);
                if(!$deposit){
                    return json(self::erres("撤单返款失败"));
                }

                //冻结
                $tradetype = 2003;
                $tradenote = "订单退款冻结";
                $freeze = $AccountModel->freeze($uid,$order_paymoney,$tradetype,$tradenote);
                if(!$freeze){
                    return json(self::erres("订单退款冻结失败"));
                }

                $refundid = $AccountModel->addDrawOrderInfo($uid,$order_paymoney,config("drawtype.order"),$paychannel,$orderid,$payorderid,$paybankorderid);
                if($refundid === false){
                    return json(self::erres("创建退款订单失败"));
                }

                $describle = "订单退款";
                if($paychannel == config("drawchannel.alipay")){
                    $Alipay = new Alipay();
                    $ret = $Alipay->toRefund($refundid,$order_paymoney,$rechargeinfo,$describle);
                    if($ret['code'] > 0){
                        //将订单状态更新为退款中
                        if($OrderModel->updateTradeOrderInfo($uid,$orderid,$OrderModel->status_waiting_refund)){
                            return json(self::sucjson());
                        }
                    }else{
                        return json(self::erres("退款请求提交第三方失败"));
                    }
                }
            }
        }else{
            if($OrderModel->updateTradeOrderInfo($uid,$orderid,$OrderModel->status_checkup_fail_refund)){
                return json(self::sucjson());
            }
        }
        return json(self::errjson());
    }
}
