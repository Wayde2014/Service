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
            ->field('a.f_sid id, a.f_adduser userid, b.f_username adduser, a.f_status status, a.f_shopname shopname, a.f_shopdesc shopdesc, a.f_shopicon shopicon, a.f_shophone shophone, a.f_address address, a.f_isbooking isbooking, a.f_opentime opentime, a.f_isaway isaway, a.f_deliverytime deliverytime, a.f_addtime addtime')
            ->join('t_admin_userinfo b','a.f_adduser = b.f_uid','left')
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
            ->field('a.f_sid id, a.f_adduser userid, a.f_shopname shopname, a.f_status status, a.f_shopdesc shopdesc, a.f_shopicon shopicon, a.f_shophone shophone, a.f_address address, a.f_cuisineid cuisineid, b.f_cname cuisinename, a.f_menulist menulist, a.f_maplon maplon, a.f_maplat maplat, a.f_sales sales, a.f_deliveryfee deliveryfee, a.f_minprice minprice, a.f_preconsume preconsume, a.f_isbooking isbooking, a.f_opentime opentime, a.f_isaway isaway, a.f_deliverytime deliverytime, a.f_addtime addtime')
            ->join('t_food_cuisine b','a.f_cuisineid = b.f_cid','left')
            ->where('a.f_sid', $shopid)
            ->find();
        return $dineshopinfo;
    }
    /**
     * 查询店铺折扣记录 根据时间段和日期
     */
    public function getDiscount($shopid, $date, $slotid){
        $discount = Db::table('t_dineshop_discount')
            ->field('f_id id, f_discount discount') 
            ->where('f_sid', $shopid)
            ->where('f_date', $date)
            ->where('f_timeslot', $slotid)
            ->find();
        return $discount;
    }
    /**
     * 新增店铺折扣信息
     */
    public function addDineshopDiscount($shopid, $date, $slotid, $discount){
        $data = array(
            'f_sid' => $shopid,
            'f_date' => $date,
            'f_timeslot' => $slotid,
            'f_discount' => $discount,
            'f_addtime' => date('Y-m-d H:i:s'),
        );
        $discountid = intval(Db::table('t_dineshop_discount')->insertGetId($data));
        return $discountid;
    }
    /**
     * 修改店铺折扣信息
     */
    public function modDineshopDiscount($id, $discount){
        $data = array("f_discount" => $discount);
        $ret = Db::table('t_dineshop_discount')->where('f_id', $id)->update($data);
        if($ret !== false){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 删除店铺折扣信息
     */
    public function delDineshopDiscount($id){
        $data = array("f_status" => 0);
        $ret = Db::table('t_dineshop_discount')->where('f_id', $id)->update($data);
        if($ret !== false){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 获取店铺折扣信息
     */
    public function getDineshopDiscount($shopid, $startdate, $endate){
        $discountlist = Db::table('t_dineshop_discount')
            ->alias('a')
            ->field('a.f_id id, a.f_sid shopid, a.f_date date, a.f_timeslot timeslotid, concat(b.f_starttime, \'-\', b.f_endtime) timeslot, a.f_discount discount, a.f_addtime addtime') 
            ->join('t_dineshop_discount_timeslot b','a.f_timeslot = b.f_id','left')
            ->where('a.f_sid', $shopid)
            ->where('a.f_status', 1)
            ->where('a.f_date',['>=',$startdate],['<',$endate])
            ->order('a.f_date asc')
            ->select();
            
        return $discountlist;
    }
    /**
     * 添加店铺折扣时间段
     */
    public function addDiscountTimeslot($startime, $endtime){
        $timeslot = Db::table('t_dineshop_discount_timeslot')->field('f_id slotid')->where('f_starttime', $startime)->where('f_endtime', $endtime)->find();
        if($timeslot){
            return $timeslot['slotid'];
        }else{
            $data = array(
                'f_starttime' => $startime,
                'f_endtime' => $endtime,
                'f_addtime' => date('Y-m-d H:i:s')
            );
            $slotid = intval(Db::table('t_dineshop_discount_timeslot')->insertGetId($data));
            return $slotid?$slotid:false;
        }
    }
    /**
     * 删除店铺折扣时间段
     */
    public function delDiscountTimeslot($slotid){
        // 启动事务
        Db::startTrans();
        try{
            Db::table('t_dineshop_discount_timeslot')->whereIn('f_id', explode(',',$slotid))->delete(); //删除折扣时间段
            Db::table('t_dineshop_discount')->whereIn('f_timeslot', explode(',',$slotid))->delete(); //删除时间段内的所有折扣信息
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
     * 获取店铺折扣时间段
     */
    public function getDiscountTimeslot(){
        $discountimeslot = Db::table('t_dineshop_discount_timeslot')
            ->field('f_id id, concat(f_starttime, \'-\', f_endtime) timeslot, f_addtime addtime')
            ->order('f_starttime asc')
            ->select();
        return $discountimeslot;
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
    /**
     * 添加店铺桌型
     */
    public function addDesk($shopid, $seatnum, $desknum){
        try{
            $data = array(
                'f_sid' => $shopid,
                'f_seatnum' => $seatnum,
                'f_amount' => $desknum,
                'f_addtime' => date('Y-m-d H:i:s')
            );
            $deskid = intval(Db::table('t_dineshop_deskinfo')->insertGetId($data));
            return $deskid;
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * 修改店铺桌型
     */
    public function modDesk($deskid, $seatnum, $desknum){
        $data = array("f_seatnum" => $seatnum, "f_amount" => $desknum);
        $ret = Db::table('t_dineshop_deskinfo')->where('f_id', $deskid)->update($data);
        if($ret !== false){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 删除店铺桌型
     */
    public function delDesk($deskid){
        $ret = Db::table('t_dineshop_deskinfo')->where('f_id', $deskid)->delete();
        return $ret<0?false:true;
    }
    /**
     * 获取店铺桌型列表
     */
    public function getDesklist($shopid){
        $desklist = Db::table('t_dineshop_deskinfo')
            ->field('f_id id, f_sid shopid, f_seatnum seatnum, f_amount desknum, f_addtime addtime')
            ->where('f_sid', $shopid)
            ->where('f_status', 1)
            ->order('f_seatnum asc')
            ->select();
        return $desklist;
    }
    /**
     * 获取店铺桌型信息
     */
    public function getDeskinfo($deskid){
        $deskinfo = Db::table('t_dineshop_deskinfo')
            ->alias('a')
            ->field('a.f_id id, a.f_sid shopid, b.f_shopname shopname, b.f_shopdesc shopdesc, b.f_shopicon shopicon, b.f_shophone shophone, b.f_address address, a.f_seatnum seatnum, a.f_amount desknum, a.f_addtime addtime')
            ->join('t_dineshop b','a.f_sid = b.f_sid','left')
            ->where('a.f_id', $deskid)
            ->where('a.f_status', 1)
            ->find();
        return $deskinfo;
    }
}