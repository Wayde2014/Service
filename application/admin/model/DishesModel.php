<?php
/**
 * Dineshop店铺信息管理类
 */
namespace app\admin\model;

use think\Model;
use think\Db;

class DishesModel extends Model
{
    /**
     * 获取菜品信息
     */
    public function getDishesList($menulist){
        $disheslist = Db::table('t_food_dishes')
            ->alias('a')
            ->field('a.f_id id, a.f_icon icon, a.f_name dishesname, a.f_price price, b.f_cname classifyname, c.f_cname cuisinename')
            ->join('t_food_classify b','a.f_classid = b.f_cid','left')
            ->join('t_food_cuisine c','a.f_cuisineid = c.f_cid','left')
            ->whereIn('a.f_id', explode(',',$menulist))
            ->select();
        return $disheslist?$disheslist:false;
    }
    
    /**
     * 删除菜品信息
     */
    public function delDishes($dishesid){
        $table_name = 'food_dishes';
        $res = Db::name($table_name)
            ->where('f_id', $dishesid)
            ->delete();
        if($res !== false){
            return true;
        }else{
            return false;
        }
    }
}