<?php
/**
 * Dineshop店铺信息管理类
 */
namespace app\admin\model;

use think\Model;
use think\Db;

class TastesModel extends Model
{
    /**
     * 获取口味信息
     */
    public function getDishesList($tasteslist){
        $tasteslist = Db::name('food_tastes')
            ->field('f_tid tid,f_tname tastes')
            ->whereIn('f_tid', explode(',',$tasteslist))
            ->select();
        return $tasteslist?$tasteslist:false;
    }
}