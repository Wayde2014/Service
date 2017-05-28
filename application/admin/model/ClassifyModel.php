<?php
/**
 * Dineshop店铺信息管理类
 */
namespace app\admin\model;

use think\Model;
use think\Db;

class ClassifyModel extends Model
{
    /**
     * 获取分类信息
     */
    public function getClassifyList(){
        $field = 'f_cid id, f_cname classname, f_lasttime lastime';
        $classifylist = Db::table('t_food_classify')->field($field)->select();
        return $classifylist?$classifylist:array();
    }
}