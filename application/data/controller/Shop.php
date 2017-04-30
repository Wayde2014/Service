<?php
namespace app\data\controller;
use \base\Base;
use think\Db;

class Shop extends Base
{
    /**
     * 获取推荐列表
     */
    public function getRecomList(){
        $num = input('num')?input('num'):4; //取推荐店铺个数
        $list = Db::query('select f_sid shopid, f_shopicon shopicon, f_shopname shopname, f_sort sort from t_dineshop_recom order by f_sort asc limit 0,:num',['num'=>intval($num)]);
        if($list && count($list) > 0){
            $this->res['code'] = 1;
            $this->res['list'] = $list;
        }
        return json($this->res);
    }
    /**
     * 获取外卖列表
     */
    public function getTakeoutList(){
        $page = input('page')?input('page'):1; //页码
        $pagesize = input('pagesize')?input('pagesize'):10; //每页数量
        $lon = input('lon')?input('lon'):'114.240668'; //经度
        $lat = input('lat')?input('lat'):'22.703796'; //纬度
        $list = Db::query('SELECT f_sid shopid, f_shopicon shopicon, f_shopname shopname, f_sales sales, f_deliveryfee deliveryfee, f_minprice minprice, f_preconsume preconsume, f_modtime modtime, distance distance FROM(SELECT *,ROUND(6378.138 *2*ASIN(SQRT(POW(SIN((:lat1*PI()/180-f_maplat*PI()/180)/2),2)+COS(:lat2*PI()/180)*COS(f_maplat*PI()/180)*POW(SIN((:lon*PI()/180-f_maplon*PI()/180)/2),2)))*1000) AS distance FROM t_dineshop where f_isaway=:isaway ORDER BY distance ASC) a LIMIT :page,:pagesize',['lon'=>floatval($lon), 'lat1'=>floatval($lat), 'lat2'=>floatval($lat), 'isaway'=>1, 'page'=>intval(($page-1)*$pagesize), 'pagesize'=>intval($pagesize)]);
        if($list && count($list) > 0){
            $this->res['code'] = 1;
            $this->res['list'] = $list;
        }
        return json($this->res);
    }
    /**
     * 获取食堂列表
     */
    public function getCanteenList(){
        $page = input('page')?input('page'):1; //页码
        $pagesize = input('pagesize')?input('pagesize'):10; //每页数量
        $lon = input('lon')?input('lon'):'114.240668'; //经度
        $lat = input('lat')?input('lat'):'22.703796'; //纬度
        $list = Db::query('SELECT f_sid shopid, f_shopicon shopicon, f_shopname shopname, f_sales sales, f_deliveryfee deliveryfee, f_minprice minprice, f_preconsume preconsume, f_modtime modtime, distance distance FROM(SELECT *,ROUND(6378.138 *2*ASIN(SQRT(POW(SIN((:lat1*PI()/180-f_maplat*PI()/180)/2),2)+COS(:lat2*PI()/180)*COS(f_maplat*PI()/180)*POW(SIN((:lon*PI()/180-f_maplon*PI()/180)/2),2)))*1000) AS distance FROM t_dineshop where f_isbooking=:isbooking ORDER BY distance ASC) a LIMIT :page,:pagesize',['lon'=>floatval($lon), 'lat1'=>floatval($lat), 'lat2'=>floatval($lat), 'isbooking'=>1, 'page'=>intval(($page-1)*$pagesize), 'pagesize'=>intval($pagesize)]);
        if($list && count($list) > 0){
            $this->res['code'] = 1;
            $this->res['list'] = $list;
        }
        return json($this->res);
    }
    /**
     * 获取店铺详情
     */
    public function getShopDetail(){
        $info = array();
        $list = array();
        $shopid = input('shopid'); //店铺ID
        if($shopid){
            $res = Db::query(
            'SELECT
                a.f_sid sid, 
                a.f_shopname shopname,
                a.f_shopicon shopicon,
                a.f_shophone shophone,
                a.f_address address,
                a.f_menulist menulist,
                a.f_sales sales,
                a.f_deliveryfee deliveryfee,
                a.f_minprice minprice,
                a.f_preconsume preconsume,
                a.f_isbooking isbooking,
                a.f_isaway isaway,
                a.f_opentime opentime,
                a.f_deliverytime deliverytime,
                b.f_cname cuisinename
            FROM
                t_dineshop a left join t_food_cuisine b on a.f_cuisineid = b.f_cid
            WHERE
                f_sid = :shopid',['shopid'=>intval($shopid)]);
            if($res){
                $info = $res[0];
                $menulist = $info['menulist'];
                $shopdishes = array();
                $list = Db::query(
                "SELECT
                    a.f_id id,
                    a.f_icon icon,
                    a.f_name dishesname,
                    a.f_price price,
                    a.f_tastesid tastesid,
                    b.f_cname classifyname,
                    c.f_cname cuisinename
                FROM
                    t_food_dishes a
                LEFT JOIN t_food_classify b ON a.f_classid = b.f_cid
                LEFT JOIN t_food_cuisine c ON a.f_cuisineid = c.f_cid
                WHERE
                    instr(concat(',','".$menulist."',','),concat(',',f_id,',')) > 0
                ORDER BY classifyname");
                if($list){
                    foreach($list as $key=>$val){
                        $shopdishes[$val['classifyname']][] = $val;
                    }
                }
                $info["shopdishes"] = $shopdishes;
            }
        }
        $this->res['code'] = 1;
        $this->res['info'] = $info;
        return json($this->res);
    }
}