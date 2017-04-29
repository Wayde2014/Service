<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 17-4-29
 * Time: 下午11:09
 */

namespace app\data\model;

use think\Model;
use think\Db;

class AccountModel extends Model
{
    public $tradetype_config = array(
        1001 => '余额充值',
        1002 => '押金充值',
        1101 => '押金退款解冻',
        1102 => '订单支付解冻',
        2001 => '押金退款冻结',
        2002 => '订单支付冻结',
        2101 => '押金退款(解冻扣款)',
        2102 => '订单支付(解冻扣款)',
    );

    /**
     * 新增充值订单信息
     * @param $uid
     * @param $paymoney
     * @param $paytype
     * @param $channel
     * @param $account
     * @param $paynote
     * @return bool|int
     */
    public function addChargeInfo($uid, $paymoney, $paytype, $channel, $account, $paynote){
        $table_name = 'user_charge_order';
        $data = array(
            'f_uid' => $uid,
            'f_paymoney' => $paymoney,
            'f_paytype' => $paytype,
            'f_channel' => $channel,
            'f_account' => $account,
            'f_paynote' => $paynote,
            'f_addtime' => date("Y-m-d H:i:s"),
        );
        $orderid = intval(Db::name($table_name)->insertGetId($data));
        if ($orderid <= 0) {
            return false;
        }
        return $orderid;
    }

    /**
     * 更新充值订单状态
     * @param $orderid
     * @param $status
     * @param $bankorderid
     * @param $bankmoney
     * @return bool
     */
    public function updateRechargeStatus($orderid, $status, $bankorderid, $bankmoney){
        $table_name = 'user_charge_order';
        $data = array(
            'f_bankorderid' => $bankorderid,
            'f_bankmoney' => $bankmoney,
            'f_status' => $status,
        );
        $retup = Db::name($table_name)
            ->where('f_id',$orderid)
            ->update($data);
        if($retup !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 存款接口(原子接口)
     */
    public function deposit(){

    }

    /**
     * 扣款接口(原子操作)
     */
    public function deduct(){

    }

    /**
     * 充值成功入账
     */
    public function rechargeMoney(){

    }

    /**
     * 提款成功扣款
     */
    public function drawMoney(){

    }
}