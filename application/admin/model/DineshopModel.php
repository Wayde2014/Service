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
     * 获取店铺信息
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