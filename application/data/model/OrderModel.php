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
    
    /**
     * 完成订单
     */
    public function finishOrder($userid, $orderid, $allmoney)
    {
        // 启动事务
        Db::startTrans();
        try{
            Db::table('t_user_info')->where('f_uid', $userid)->setDec('f_usermoney', $allmoney);
            Db::table('t_orders')->where('f_oid', $orderid)->update(array('f_status' => 2));
            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }
    
    /**
     * 检测订单是否已经存在
     */
    public function checkOrder($userid, $shopid, $orderdetail, $ordertype)
    {
        $table_name = 'orders';
        $check = Db::name($table_name)
            ->field('f_oid orderid')
            ->where('f_shopid', $shopid)
            ->where('f_userid', $userid)
            ->where('f_orderdetail', $orderdetail)
            ->where('f_type', $ordertype)
            ->find();
        return $check?$check['orderid']:false;
    }
    
    /**
     * 获取订单详情
     */
    public function getOrderinfo($orderid)
    {
        $table_name = 'orders';
        $orderinfo = Db::name($table_name)
            ->field('f_oid orderid,f_shopid shopid,f_userid userid,f_type ordertype,f_status status,f_orderdetail orderdetail,f_ordermoney ordermoney,f_deliverymoney deliverymoney,f_allmoney allmoney,f_paymoney paymoney,f_paytype paytype,f_mealsnum mealsnum,f_startime startime,f_endtime endtime,f_deliveryid deliveryid,f_deliverytime deliverytime,f_addressid addressid')
            ->where('f_oid', $orderid)
            ->find();
        return $orderinfo?$orderinfo:false;
    }
}