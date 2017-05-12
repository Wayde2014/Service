<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 17-4-25
 * Time: 下午9:28
 */
namespace app\admin\model;

use think\Model;
use think\Db;

class OrderModel extends Model
{
    /**
     * 获取外卖订单列表
     */
    public function getTakeoutlist($startime, $endtime, $shopname = '', $page = 1, $pagesize = 20)
    {
        $where = array(
            'a.f_type' => 1,
            'a.f_addtime' => array('between', [$startime.' 00:00:00', $endtime.' 59:59:59'])
        );
        if(!empty($shopname)){
            $where['b.f_shopname'] = $shopname;
        }
        $allnum = Db::table('t_orders')->alias('a')->join('t_dineshop b','a.f_shopid = b.f_sid','left')->where($where)->count();
        $orderlist = Db::table('t_orders')
            ->alias('a')
            ->field('a.f_oid orderid,a.f_shopid shopid,b.f_shopname shopname,a.f_userid userid,a.f_type ordertype,a.f_status status,a.f_orderdetail orderdetail,a.f_ordermoney ordermoney,a.f_deliverymoney deliverymoney,a.f_allmoney allmoney,a.f_paymoney paymoney,a.f_paytype paytype,d.f_name recipientname,d.f_mobile recipientmobile,c.f_username deliveryname,c.f_mobile deliverymobie,a.f_deliverytime deliverytime,CONCAT(d.f_province,d.f_city,d.f_address) deliveryaddress,a.f_addtime addtime')
            ->join('t_dineshop b','a.f_shopid = b.f_sid','left')
            ->join('t_store_distripersion c','a.f_deliveryid = c.f_id','left')
            ->join('t_user_address_info d','a.f_addressid = d.f_id','left')
            ->where($where)
            ->order('a.f_addtime desc')
            ->page($page, $pagesize)
            ->select();
        return array(
            "allnum" => $allnum,
            "orderlist" => $orderlist
        );
    }
    /**
     * 获取食堂订单列表
     */
    public function getEatinlist($startime, $endtime, $shopname = '', $page = 1, $pagesize = 20)
    {
        $where = array(
            'a.f_type' => 2,
            'a.f_addtime' => array('between', [$startime.' 00:00:00', $endtime.' 59:59:59'])
        );
        if(!empty($shopname)){
            $where['b.f_shopname'] = $shopname;
        }
        $allnum = Db::table('t_orders')->alias('a')->join('t_dineshop b','a.f_shopid = b.f_sid','left')->where($where)->count();
        $orderlist = Db::table('t_orders')
            ->alias('a')
            ->field('a.f_oid orderid,a.f_shopid shopid,b.f_shopname shopname,a.f_userid userid,a.f_type ordertype,a.f_status status,a.f_orderdetail orderdetail,a.f_ordermoney ordermoney,a.f_deliverymoney deliverymoney,a.f_allmoney allmoney,a.f_paymoney paymoney,a.f_paytype paytype,a.f_mealsnum mealsnum,a.f_startime startime,a.f_endtime endtime,a.f_addtime addtime')
            ->join('t_dineshop b','a.f_shopid = b.f_sid','left')
            ->where($where)
            ->order('a.f_addtime desc')
            ->page($page, $pagesize)
            ->select();
        return array(
            "allnum" => $allnum,
            "orderlist" => $orderlist
        );
    }    
    /**
     * 获取订单详情
     */
    public function deliveryOrder($orderid, $distripid)
    {
        $res = Db::table('t_orders')->where('f_oid', $orderid)->update(array('f_status' => 3, 'f_deliveryid' => $distripid));
        return $res;
    }
}