<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 17-4-25
 * Time: 下午9:28
 */
namespace app\data\model;

use think\Model;
use think\Db;

class OrderModel extends Model
{
    /**
     * 新增外卖订单
     * @params $userid 用户ID
     * @params $shopid 店铺ID
     * @params $orderdetail 订单详情
     * @params $ordermoney 订单金额
     * @params $deliverymoney 配送费
     * @params $allmoney 订单总金额
     * @params $paytype 支付方式
     * @params $deliverytime 配送时间
     * @params $addressid 配送地址ID
     * @return \think\response\Json
     */
    public function addTakeoutOrders($userid, $shopid, $orderdetail, $ordermoney, $deliverymoney, $allmoney, $paytype, $deliverytime, $addressid)
    {
        $table_name = 'orders';
        $data = array(
            'f_userid' => $userid,
            'f_shopid' => $shopid,
            'f_type' => 1,
            'f_orderdetail' => $orderdetail,
            'f_ordermoney' => $ordermoney,
            'f_deliverymoney' => $deliverymoney,
            'f_allmoney' => $allmoney,
            'f_paytype' => $paytype,
            'f_deliverytime' => $deliverytime,
            'f_addressid' => $addressid,
            'f_addtime' => date("Y-m-d H:i:s"),
        );
        $orderid = intval(Db::name($table_name)->insertGetId($data));
        if ($orderid <= 0) {
            return false;
        }
        return $orderid;
    }
    /**
     * 新增食堂订单
     * @params $userid 用户ID
     * @params $shopid 店铺ID
     * @params $orderdetail 订单详情
     * @params $ordermoney 订单金额
     * @params $deliverymoney 配送费
     * @params $allmoney 订单总金额
     * @params $paytype 支付方式
     * @params $mealsnum 就餐人数
     * @params $startime 就餐开始时间
     * @params $endtime 就餐结束时间
     * @return \think\response\Json
     */
    public function addEatinOrders($userid, $shopid, $orderdetail, $ordermoney, $deliverymoney, $allmoney, $paytype, $mealsnum, $startime, $endtime)
    {
        $table_name = 'orders';
        $data = array(
            'f_userid' => $userid,
            'f_shopid' => $shopid,
            'f_type' => 2,
            'f_orderdetail' => $orderdetail,
            'f_ordermoney' => $ordermoney,
            'f_deliverymoney' => $deliverymoney,
            'f_allmoney' => $allmoney,
            'f_paytype' => $paytype,
            'f_mealsnum' => $mealsnum,
            'f_startime' => $startime,
            'f_endtime' => $endtime,
            'f_addtime' => date("Y-m-d H:i:s"),
        );
        $orderid = intval(Db::name($table_name)->insertGetId($data));
        if ($orderid <= 0) {
            return false;
        }
        return $orderid;
    }
}