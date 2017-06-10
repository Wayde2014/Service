<?php
namespace app\data\controller;
use \base\Base;
use think\Db;
use \app\data\model\DineshopModel;
use \app\data\model\DishesModel;

class Shop extends Base
{
    /**
     * 获取推荐列表
     */
    public function getRecomList(){
        $num = input('num')?input('num'):4; //取推荐店铺个数
        $list = Db::query('select f_sid shopid, f_shopicon shopicon, f_shopname shopname, f_sort sort from t_dineshop_recom order by f_sort asc limit 0,:num',['num'=>intval($num)]);
        $this->res['code'] = 1;
        if($list && count($list) > 0){
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
        $res_info = array();
        $res_list = array();
        $pageinfo = Db::query('SELECT count(1) cnt FROM t_dineshop where f_isaway = 1');
        if(!empty($pageinfo)){
            $totalpage = ceil($pageinfo[0]['cnt']/$pagesize);
            $res_info = array("totalpage" => $totalpage);
        }
        $list = Db::query('SELECT f_sid shopid, f_shopicon shopicon, f_shopname shopname, f_sales sales, f_deliveryfee deliveryfee, f_minprice minprice, f_minconsume minconsume, f_preconsume preconsume,f_servicecharge servicecharge, f_modtime modtime, distance distance FROM(SELECT *,ROUND(6378.138 *2*ASIN(SQRT(POW(SIN((:lat1*PI()/180-f_maplat*PI()/180)/2),2)+COS(:lat2*PI()/180)*COS(f_maplat*PI()/180)*POW(SIN((:lon*PI()/180-f_maplon*PI()/180)/2),2)))*1000) AS distance FROM t_dineshop where f_isaway=:isaway ORDER BY distance ASC) a LIMIT :page,:pagesize',['lon'=>floatval($lon), 'lat1'=>floatval($lat), 'lat2'=>floatval($lat), 'isaway'=>1, 'page'=>intval(($page-1)*$pagesize), 'pagesize'=>intval($pagesize)]);
        if($list && count($list) > 0){
            $res_list = $list;
        }
        return json(self::sucjson($res_info,$res_list));
    }
    /**
     * 获取食堂列表
     */
    public function getCanteenList(){
        $page = input('page')?input('page'):1; //页码
        $pagesize = input('pagesize')?input('pagesize'):10; //每页数量
        $lon = input('lon')?input('lon'):'114.240668'; //经度
        $lat = input('lat')?input('lat'):'22.703796'; //纬度
        $res_info = array();
        $res_list = array();
        $pageinfo = Db::query('SELECT count(1) cnt FROM t_dineshop where f_isbooking=1');
        if(!empty($pageinfo)){
            $totalpage = ceil($pageinfo[0]['cnt']/$pagesize);
            $res_info = array("totalpage" => $totalpage);
        }
        $list = Db::query('SELECT f_sid shopid, f_shopicon shopicon, f_shopname shopname, f_sales sales, f_deliveryfee deliveryfee, f_minprice minprice, f_minconsume minconsume, f_preconsume preconsume, f_servicecharge servicecharge, f_modtime modtime, distance distance FROM(SELECT *,ROUND(6378.138 *2*ASIN(SQRT(POW(SIN((:lat1*PI()/180-f_maplat*PI()/180)/2),2)+COS(:lat2*PI()/180)*COS(f_maplat*PI()/180)*POW(SIN((:lon*PI()/180-f_maplon*PI()/180)/2),2)))*1000) AS distance FROM t_dineshop where f_isbooking=:isbooking ORDER BY distance ASC) a LIMIT :page,:pagesize',['lon'=>floatval($lon), 'lat1'=>floatval($lat), 'lat2'=>floatval($lat), 'isbooking'=>1, 'page'=>intval(($page-1)*$pagesize), 'pagesize'=>intval($pagesize)]);
        if($list && count($list) > 0){
            $res_list = $list;
        }
        return json(self::sucjson($res_info,$res_list));
    }
    /**
     * 获取店铺详情
     */
    public function getShopDetail(){
        $info = array();
        $list = array();
        $shopid = input('shopid'); //店铺ID
        if($shopid){
            $DineshopModel = new DineshopModel();
            $res = $DineshopModel->getShopInfo($shopid);
            if($res){
                $info = $res;
                $menulist = $info['menulist'];
                $shopdishes = array();
                $DishesModel = new DishesModel();
                $reslist = $DishesModel->getDishesListBysid($shopid);
                if($reslist){
                    foreach($reslist as $key=>$val){
                        $shopdishes[$val['classifyname']][] = $val;
                    }
                }
                $list = array_keys($shopdishes);
                $info["shopdishes"] = $shopdishes;
                $info["discounttimeslot"] = $DineshopModel->getDiscountTimeslot();
            }
        }
        return json($this->sucres($info,$list));
    }
    /**
     * 获取折扣时间段
     */
    public function getDiscountTimeslot(){
        $info = array();
        $list = array();
        $DineshopModel = new DineshopModel();
        $list = $DineshopModel->getDiscountTimeslot();
        return json($this->sucjson($info, $list));
    }
    /**
     * 获取店铺折扣信息
     */
    public function getDineshopDiscount(){
        $info = array();
        $list = array();
        $shopid = input('shopid'); //店铺ID
        if(empty($shopid)){
            return json($this->errjson(-20003));
        }
        $slotid = input('slotid'); //折扣信息ID
        $date = input('date'); //折扣时间
        if(empty($slotid) || empty($date)){
            return json($this->errjson(-20001));
        }
        if(!check_datetime($date, 'yyyy-mm-dd')){
            return json($this->errjson(-20002));
        } 
        $discount = array();
        $DineshopModel = new DineshopModel();
        $res = $DineshopModel->getDineshopDiscount($shopid, $slotid, $date);
        if($res){
            if($res['discount']){
                preg_match_all('/(\d+)\|(\d+)\@(([1-9]\d*|0)(\.\d{1,2})?)/i', $res['discount'], $match);
                $discount = array_combine($match[1], $match[0]);
            }
            $DishesModel = new DishesModel();
            $reslist = $DishesModel->getDishesListBysid($shopid);
            foreach($reslist as $key => $val){
                $classifyname = $val['classifyname'];
                if(isset($discount[$val['id']])){
                    preg_match('/(\d+)\|(\d+)\@(([1-9]\d*|0)(\.\d{1,2})?)/i', $discount[$val['id']], $match);
					$floatTemp = floatval(str_replace(',','',$val['price']));
                    if($match[2] == 1){
                        $reslist[$key]['discountprice'] = $floatTemp * $match[3];
                    }elseif($match[2] == 2){
                        $reslist[$key]['discountprice'] = $floatTemp - $match[3];
                    }
                }
                if(isset($reslist[$key]['discountprice'])){
                    $reslist[$key]['discountprice'] = number_format($reslist[$key]['discountprice'] , 2, ".", "");
                }else{
                    $default_discount = $val['default_discount'];
                    $floatTemp = floatval(str_replace(',','',$val['price']));
                    $discount_price = $floatTemp * $default_discount;
                    $reslist[$key]['discountprice'] = number_format($discount_price , 2, ".", "");
                }
                if(!isset($info[$classifyname])) $info[$classifyname] = array();
                array_push($info[$classifyname], $reslist[$key]);
            }
        }
        return json($this->sucjson($info, $list));
    }

    /**
     * 获取桌型放号信息
     */
    public function getDeskSellInfo(){
        $shopid = input('shopid'); //店铺ID
        if(empty($shopid)){
            return json($this->errjson(-20003));
        }
        $slotid = input('slotid'); //折扣信息ID
        $date = input('date'); //折扣时间
        if(empty($slotid) || empty($date)){
            return json($this->errjson(-20001));
        }
        if(!check_datetime($date, 'yyyy-mm-dd')){
            return json($this->errjson(-20002));
        }

        $list = array();
        $DineshopModel = new DineshopModel();

        //取某店铺某日期某时间段放号的桌型信息
        $sellinfo = $DineshopModel->getDeskSellIinfo($shopid,$date,$slotid);
        if(!empty($sellinfo)){
            $desk_num_arr = array();
            foreach($sellinfo as $row){
                $desk_num_str = explode('$',$row['sellinfo']);
                if(!empty($desk_num_str)){
                    foreach($desk_num_str as $v){
                        $temp = explode('@',$v);
                        $desk_num_arr[$temp[0]] = $temp[1];
                    }
                }
            }
            //根据放号桌型ID获取桌型详细信息
            $deskinfo = $DineshopModel->getDeskInfo($shopid,array_keys($desk_num_arr));
            if(!empty($deskinfo)){
                foreach($deskinfo as $row){
                    $sellnum = $desk_num_arr[$row['deskid']];
                    $orderamount = $row["orderamount"];
                    $new_sellnum = $sellnum - $orderamount;
                    $list[$row['deskid']] = array(
                        "shopid" => $row["shopid"],
                        "deskid" => $row["deskid"],
                        "seatnum" => $row["seatnum"],
                        "sellnum" => $new_sellnum >= 0 ? $new_sellnum : 0,
                        "usable" => $new_sellnum > 0 ? true : false,
                    );
                }
            }
        }
        $info = array();
        return json($this->sucjson($info, array_values($list)));
    }
}