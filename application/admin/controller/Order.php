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
        $shopid = input('shopid',''); //店铺ID或名称
        $orderid = input('orderid',''); //订单ID
        if(!empty($orderid) && !is_numeric($orderid)){
            return json($this->erres('订单ID必须为数字'));
        }
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
            $res = $OrderModel->getTakeoutlist($startime, $endtime, $shopid, $orderid, $page, $pagesize);
        }else{
            $res = $OrderModel->getEatinlist($startime, $endtime, $shopid, $orderid, $page, $pagesize);
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
        $uid = input('uid');
        $userid = input('userid');
        $orderid = input('orderid');
        $status = intval(input('status',-1));
        $distripid = input('distripid'); //配送员ID;
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $OrderModel = new OrderModel();
        $orderinfo =$OrderModel->getOrderinfo($userid, $orderid);
        if(empty($orderinfo)){
            return json($this->erres("订单信息不存在"));
        }
        $order_status = $orderinfo['status'];
        $order_type = $orderinfo['ordertype'];
        $order_startime = $orderinfo['startime'];
        $order_endtime = $orderinfo['endtime'];
        if($order_type==1 && !in_array($status,array(2,3))){
            return json(self::erres("外卖订单状态错误"));
        }elseif($order_type==2 && !in_array($status,array(2,5,6))){
            return json(self::erres("堂食订单状态错误"));
        }
        $data = array();
        switch($status){
            case 2: //当前已付款，需设置成配送中/就餐中
                if($order_type == 1){
                    if(empty($distripid)) return json($this->erres("缺少参数"));
                    $data['status'] = 3;
                    $data['distripid'] = $distripid;
                }else{
                    if(strtotime($order_startime) < time()){
                        return json(self::erres("当前堂食订单尚未到就餐开始时间!"));
                    }
                    $data['status'] = 5;
                }
                break;
            case 3: //当前配送中，需设置成配送完成
                $data['status'] = 100;
                break;
            case 5: //当前用餐中，需设置成用餐结束
                $data['status'] = 100;
                if(strtotime($order_endtime) < time()){
                    return json(self::erres("当前堂食订单尚未到就餐截止时间!"));
                }
                break;
            case 6: //当前申请打包中，需设置成打包完成
                $data['status'] = 90;
                break;
            default:
                return json($this->erres("参数错误"));
        }
        if($order_status == $data['status']){
            return json(self::sucjson());
        }
        $info = $OrderModel->processOrder($orderid, $data);
        if(!$info) return json($this->erres("更新失败"));
        return json($this->sucjson($info));
    }

    /**
     * 审核退款订单
     */
    public function checkupCancelOrder(){
        $userid = input('userid');
        $orderid = input('orderid');
        $checkupstatus = input('checkupstatus',1);    //审核结果 0-不通过，1-通过
        if(!$this->checkAdminLogin()){
            return json($this->errjson(-10001));
        }
        $OrderModel = new OrderModel();
        $orderinfo =$OrderModel->getOrderinfo($userid, $orderid);
        if(empty($orderinfo)){
            return json($this->erres("订单信息不存在"));
        }
        $order_status = $orderinfo['status'];
        $order_paytype = $orderinfo['paytype'];
        $order_paymoney = $orderinfo['paymoney'];
        $order_type = $orderinfo['ordertype'];
        $order_deskid = $orderinfo['deskid'];
        if($order_status != $OrderModel->status_waiting_checkup_refund){
            return json($this->erres("订单非待审核退款状态"));
        }
        if($checkupstatus == 1){
            //审核通过
            if(!$OrderModel->updateTradeOrderInfo($userid,$orderid,$OrderModel->status_checkup_suc_refund)){
                return json(self::erres("退款订单审核失败"));
            }
            if($order_type == 2){
                $OrderModel->cancelTradeOrderDeskOrdernum($order_deskid);
            }

            if($order_paytype == 0){
                //余额支付，撤单返款即完成
                //撤单返款
                $Account = new AccountModel();
                $tradetype = 1004;
                $deposit = $Account->deposit($userid,$order_paymoney,$tradetype,$orderid);
                if(!$deposit){
                    return json(self::erres("撤单返款失败"));
                }
                if($OrderModel->updateTradeOrderInfo($userid,$orderid,$OrderModel->status_refund_suc)){
                    return json(self::sucjson());
                }
            }else{
                //支付宝支付or微信支付
                //检查订单对应充值信息
                $AccountModel = new AccountModel();
                $rechargeinfo = $AccountModel->getTradeOrderRechargeInfo($userid,$orderid);
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
                if(!in_array($paychannel,$FrontUser->allow_drawchannel)){
                    return json(self::erres("该支付订单当前不支持原路退回"));
                }

                //撤单返款
                $Account = new AccountModel();
                $tradetype = 1004;
                $deposit = $Account->deposit($userid,$order_paymoney,$tradetype,$orderid);
                if(!$deposit){
                    return json(self::erres("撤单返款失败"));
                }

                //冻结
                $tradetype = 2003;
                $tradenote = "订单退款冻结";
                $freeze = $AccountModel->freeze($userid,$order_paymoney,$tradetype,$tradenote);
                if(!$freeze){
                    return json(self::erres("订单退款冻结失败"));
                }

                $refundid = $AccountModel->addDrawOrderInfo($userid,$order_paymoney,config("drawtype.order"),$paychannel,$orderid,$payorderid,$paybankorderid);
                if($refundid === false){
                    return json(self::erres("创建退款订单失败"));
                }

                $describle = "订单退款";
                if($paychannel == config("drawchannel.alipay")){
                    $Alipay = new Alipay();
                    $ret = $Alipay->toRefund($refundid,$order_paymoney,$rechargeinfo,$describle);
                    if($ret['code'] > 0){
                        //将订单状态更新为退款中
                        if($OrderModel->updateTradeOrderInfo($userid,$orderid,$OrderModel->status_waiting_refund)){
                            return json(self::sucjson());
                        }
                    }else{
                        return json(self::erres("退款请求提交第三方失败"));
                    }
                }
            }
        }else{
            if($OrderModel->updateTradeOrderInfo($userid,$orderid,$OrderModel->status_checkup_fail_refund)){
                return json(self::sucjson());
            }
        }
        return json(self::errjson());
    }
}
