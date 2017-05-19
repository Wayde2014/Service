<?php
/**
 * 支付宝异步通知处理
 * User: Administrator
 * Date: 17-5-16
 * Time: 下午8:31
 */

namespace app\notify\controller;

use think\Exception;
use think\Log;
use think\Request;
use third\Alipay as AlipayBase;
use app\data\model\AccountModel as Account;


class Alipay
{
    public function index()
    {
        try{
            $notify_args = Request::instance()->post(false);
            //$notify_args = Request::instance()->get(false);
            Log::record($notify_args,'debug');

            //剔除sign、sign_type参数，开始验签
            if(empty($ori_post)){
                exception("异步通知获取post参数为空");
            }
            $sign_type = $notify_args['sign_type'];
            $sign = $notify_args['sign'];
            $charset = $notify_args['charset'];
            foreach($notify_args as $k=>$v){
                if(in_array($k,array('sign_type','sign'))){
                    unset($notify_args[$k]);
                }
            }
            $AlipayBase = new AlipayBase();
            $AlipayBase->postCharset = $charset;
            if(!$AlipayBase->verify($notify_args,$sign,$sign_type)){
                exception("签名验证失败");
            }

            //签名验证通过，处理通知报文
            $app_id = $notify_args['app_id'];    //支付宝分配给开发者的应用Id
            $seller_id = $notify_args['seller_id']; //卖家支付宝用户号
            $trade_no = $notify_args['trade_no'];    //支付宝交易号
            $out_trade_no = $notify_args['out_trade_no'];    //商户订单号
            $trade_status = $notify_args['trade_status'];    //交易状态
            $total_amount = $notify_args['total_amount'];   //订单金额

            //可为空参数
            $buyer_logon_id = '';   //买家支付宝账号
            if(array_key_exists('buyer_logon_id',$notify_args)){
                $buyer_logon_id = $notify_args['buyer_logon_id'];
            }
            $describle = ''; //商品描述
            if(array_key_exists('body',$notify_args)){
                $describle = $notify_args['body'];
            }

            //验证此次通知内容是否有效
            if($app_id != $AlipayBase->appid || $seller_id != $AlipayBase->uuid){
                exception("无效通知");
            }
            if(!in_array($trade_status,array('TRADE_SUCCESS','TRADE_FINISHED'))){
                exception("暂不处理的通知交易状态");
            }

            //处理支付通知
            $Account = new Account();
            $orderid = $out_trade_no;
            $orderinfo = $Account->getRechargeOrderInfo($orderid);
            if(empty($orderinfo)){
                exception('该笔充值订单不存在');
            }
            $channel = intval($orderinfo['channel']);
            if($channel != 1001){
                exception('该笔充值订单非支付宝渠道');
            }
            $order_status = intval($orderinfo['status']);
            if($order_status != 0){
                Log::record("充值订单[".$orderid."]已处理完成,当前状态-".$order_status,'info');
                exit("success");
            }
            $order_money = number_format($orderinfo['paymoney'],2,'.','');
            $bankmoney = number_format($total_amount,2,'.','');
            if($order_money != $bankmoney){
                exception("充值订单[".$orderid."]网站金额[".$order_money."]与支付宝金额[".$bankmoney."]不一致");
            }

            //充值成功，入账处理
            $result = $Account->rechargeSuc($orderid,$trade_no,$bankmoney,$buyer_logon_id,$describle);
            if(!$result){
                exception("充值订单[".$orderid."]入账失败");
            }else{
                Log::record("充值订单[".$orderid."]入账处理成功",'info');
                exit("success");
            }
        }catch (Exception $e){
            Log::record($e,'error');
            exit('fail');
        }
        exit("fail");
    }
}