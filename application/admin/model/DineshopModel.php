<?php
/**
 * Dineshop店铺信息管理类
 */
namespace app\admin\model;

use think\Model;
use think\Db;

class DineshopModel extends Model
{
    /**
     * 获取店铺信息列表
     */
    public function getDineshopList($page = 1, $pagesize = 20){
        $allnum = Db::table('t_admin_dineshop')->count();
        $dineshoplist = Db::table('t_admin_dineshop')
            ->alias('a')
            ->field('a.f_sid id, a.f_adduser userid, a.f_status status, a.f_shopdesc shopdesc, a.f_shopicon shopicon, a.f_shophone shophone, a.f_address address, a.f_cuisineid cuisineid, b.f_cname cuisinename, a.f_menulist menulist, a.f_maplon maplon, a.f_maplat maplat, a.f_sales sales, a.f_deliveryfee deliveryfee, a.f_minprice minprice, a.f_preconsume preconsume, a.f_isbooking isbooking, a.f_opentime opentime, a.f_isaway isaway, a.f_deliverytime deliverytime, a.f_addtime addtime')
            ->join('t_food_cuisine b','a.f_cuisineid = b.f_cid','left')
            ->order('a.f_addtime desc')
            ->page($page, $pagesize)
            ->select();
        return array(
            "allnum" => $allnum,
            "dineshoplist" => $dineshoplist
        );
    }
    /**
     * 获取店铺信息
     */
    public function getDineshopInfo($shopid){
        $dineshopinfo = Db::table('t_admin_dineshop')
            ->alias('a')
            ->field('a.f_sid id, a.f_adduser userid, a.f_status status, a.f_shopdesc shopdesc, a.f_shopicon shopicon, a.f_shophone shophone, a.f_address address, a.f_cuisineid cuisineid, b.f_cname cuisinename, a.f_menulist menulist, a.f_maplon maplon, a.f_maplat maplat, a.f_sales sales, a.f_deliveryfee deliveryfee, a.f_minprice minprice, a.f_preconsume preconsume, a.f_isbooking isbooking, a.f_opentime opentime, a.f_isaway isaway, a.f_deliverytime deliverytime, a.f_addtime addtime')
            ->join('t_food_cuisine b','a.f_cuisineid = b.f_cid','left')
            ->where('a.f_sid', $shopid)
            ->find();
        return $dineshopinfo;
    }
    /**
     * 获取店铺配送员信息
     */
    public function getDistripList($shopid){
        $distriplist = Db::table('t_dineshop_distripersion')
            ->field('f_id id, f_dineshopid shopid, f_id id, f_username distripname, f_mobile distripmobile')
            ->where('f_state', 0)
            ->where('f_dineshopid', $shopid)
            ->select();
        return $distriplist;
    }
}