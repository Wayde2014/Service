<?php
namespace app\data\controller;

use base\Base;
use \app\data\model\UserAddressModel;

class Order extends Base
{
    /**
     * 新增订单
     * @return \think\response\Json
     */
    //http://shanwei.boss.com/data/order/addOrders?uid=10002&shopid=8&orderdetail=1@1,2@1,23@1&ordermoney=216&deliverymoney=9&allmoney=225&paytype=0&ordertype=1&deliverytime=2017-05-02%2012:00:00&addressid=1
    //http://shanwei.boss.com/data/order/addOrders?uid=10002&shopid=8&orderdetail=1@1,2@1,23@1&ordermoney=216&deliverymoney=9&allmoney=225&paytype=0&ordertype=2&mealsnum=2&startime=2017-05-02%2012:00:00&endtime=2017-05-02%2012:00:00
    public function addOrders()
    {
        $uid = input('uid'); //用户ID
        $shopid = input('shopid'); //店铺ID
        $orderdetail = input('orderdetail'); //订单明细
        $ordermoney = floatval(input('ordermoney','0')); //订单金额
        $deliverymoney = floatval(input('deliverymoney','0')); //配送费
        $allmoney = floatval(input('allmoney','0')); //订单总金额
        $paytype = input('paytype'); //支付方式
        $ordertype = input('ordertype'); //订单类型（1,外卖订单  2,食堂订单）
        $deliverytime = input('deliverytime'); //外卖 配送时间
        $addressid = input('addressid'); //外卖 配送地址ID
        $mealsnum = input('mealsnum'); //食堂就餐 就餐人数
        $startime = input('startime'); //食堂订餐 开始时间
        $endtime = input('endtime'); //食堂订餐 结束时间
        
        if(!$shopid) return json($this->erres('未指定订单店铺'));
        if(!$orderdetail) return json($this->erres('订单不能为空'));
        if($ordermoney == 0 || $allmoney == 0 || $ordermoney + $deliverymoney != $allmoney){
            return json($this->erres('订单金额错误')); 
        }
        if($paytype == '') return json($this->erres('请选择支付方式'));
        if(!in_array($ordertype, array('1','2'))) return json($this->erres('订单类型错误'));
        if($ordertype == 1){
            if(!$deliverytime) return json($this->erres('请选择配送时间'));
            if(!check_datetime($deliverytime)) return json($this->erres('配送时间格式不对'));
            if(!$addressid) return json($this->erres('请选择配送地址'));
        }else if($ordertype == 2){
            if(!$mealsnum) return json($this->erres('请选择就餐人数'));
            if(!$startime) return json($this->erres('请选择预计就餐开始时间'));
            if(!check_datetime($startime)) return json($this->erres('就餐时间格式不对'));
            if(!$endtime) return json($this->erres('请选择预计就餐结束时间'));
            if(!check_datetime($endtime)) return json($this->erres('就餐时间格式不对'));
        }
        //判断用户登录
        if($this->checkLogin() === false) return json($this->erres('用户未登录，请先登录'));
        //验证用户
        
        //验证店铺
        
        //验证订单金额
        
        //验证外卖配送地址
        if($ordertype == 1){
            
        }
        echo '222222222';
    }
}
