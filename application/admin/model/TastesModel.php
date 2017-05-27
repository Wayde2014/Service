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
    public function getTastesList($tasteslist){
        $field = 'f_tid id, f_tname tastes, f_lasttime lastime';
        if($tasteslist){
            $tasteslist = Db::table('t_food_tastes')->field($field)->whereIn('f_tid', explode(',',$tasteslist))->select();
        }else{
            $tasteslist = Db::table('t_food_tastes')->field($field)->select();
        }
        return $tasteslist?$tasteslist:array();
    }
}