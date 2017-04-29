<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 17-4-29
 * Time: 下午11:09
 */

namespace app\data\model;

use think\Exception;
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
    private $paysuc = 100;
    private $payfail = -100;
    private $drawsuc = 100;
    private $drawfail = -100;

    /**
     * 新增充值订单信息
     * @param $uid
     * @param $paymoney
     * @param $paytype
     * @param $channel
     * @return bool|int
     */
    public function addRechargeOrderInfo($uid, $paymoney, $paytype, $channel){
        $table_name = 'user_recharge_order';
        $data = array(
            'f_uid' => $uid,
            'f_paymoney' => $paymoney,
            'f_paytype' => $paytype,
            'f_channel' => $channel,
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
     * @param $account
     * @param $paynote
     * @return bool
     */
    public function updateRechargeOrderStatus($orderid, $status, $bankorderid, $bankmoney, $account, $paynote){
        $table_name = 'user_recharge_order';
        $data = array(
            'f_bankorderid' => $bankorderid,
            'f_bankmoney' => $bankmoney,
            'f_account' => $account,
            'f_paynote' => $paynote,
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
     * 新增提款订单信息
     * @param $uid
     * @param $drawmoney
     * @param $drawtype
     * @param $channel
     * @return bool|int
     */
    public function addDrawOrderInfo($uid, $drawmoney, $drawtype, $channel){
        $table_name = 'user_draw_order';
        $data = array(
            'f_uid' => $uid,
            'f_drawmoney' => $drawmoney,
            'f_drawtype' => $drawtype,
            'f_channel' => $channel,
            'f_addtime' => date("Y-m-d H:i:s"),
        );
        $orderid = intval(Db::name($table_name)->insertGetId($data));
        if ($orderid <= 0) {
            return false;
        }
        return $orderid;
    }

    /**
     * 更新提款订单状态
     * @param $orderid
     * @param $status
     * @param $bankorderid
     * @param $bankmoney
     * @param $account
     * @param $drawnote
     * @return bool
     */
    public function updateDrawOrderStatus($orderid, $status, $bankorderid, $bankmoney, $account, $drawnote){
        $table_name = 'user_draw_order';
        $data = array(
            'f_bankorderid' => $bankorderid,
            'f_bankmoney' => $bankmoney,
            'f_account' => $account,
            'f_drawnote' => $drawnote,
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
     * 通过UID获取用户信息
     * @param $uid
     * @return array|false|\PDOStatement|string|Model
     */
    public function getUserInfo($uid){
        $table_name = 'user_info';
        $userinfo = Db::name($table_name)
            ->where('f_uid',$uid)
            ->field('f_usermoney as usermoney')
            ->field('f_freezemoney as freezemoney')
            ->field('f_depositmoney as depositmoney')
            ->find();
        return $userinfo;
    }

    /**
     * 存款接口(原子接口)
     * @param $uid
     * @param $money
     * @param $tradetype
     * @param $orderid
     * @return bool
     */
    public function deposit($uid, $money, $tradetype, $orderid){
        $table_userinfo = 'user_info';
        $talbe_paylog = 'user_paylog';
        $inout = 1;
        Db::startTrans();
        try{
            //获取用户当前账户信息
            $userinfo = self::getUserInfo($uid);
            $ori_usermoney = $userinfo['usermoney'];
            $ori_freezemoney = $userinfo['freezemoney'];
            $ori_depositmoney = $userinfo['depositmoney'];

            //重新计算余额信息
            $usermoney = $ori_usermoney;
            $freezemoney = $ori_freezemoney;
            $depositmoney = $ori_depositmoney;
            switch($tradetype){
                case 1001:
                case 1102:
                    //余额充值
                    //订单支付解冻
                    $usermoney += $money;
                    break;
                case 1002:
                case 1101:
                    //押金充值
                    //押金退款解冻
                    $depositmoney += $money;
                    break;
            }
            if($usermoney < 0 || $freezemoney < 0 || $depositmoney < 0){
                exception('账户余额不能小于0');
            }

            //入账
            $data_info = array(
                'f_usermoney' => $usermoney,
                'f_freezemoney' => $freezemoney,
                'f_depositmoney' => $depositmoney,
            );
            Db::name($table_userinfo)
                ->where('f_uid',$uid)
                ->update($data_info);

            //记录账户流水
            $tradenote = $this->tradetype_config[$tradetype];
            $data_paylog = array(
                'f_uid' => $uid,
                'f_inout' => $inout,
                'f_trademoney' => $money,
                'f_tradetype' => $tradetype,
                'f_suborder' => $orderid,
                'f_tradenote' => $tradenote,
            );
            Db::name($talbe_paylog)
                ->insert($data_paylog);
            Db::commit();
            return true;
        }catch (Exception $e){
            Db::rollback();
            return false;
        }
    }

    /**
     * 扣款接口(原子操作)
     * @param $uid
     * @param $money
     * @param $tradetype
     * @param $orderid
     * @return bool
     */
    public function deduct($uid, $money, $tradetype, $orderid){
        $table_userinfo = 'user_info';
        $talbe_paylog = 'user_paylog';
        $inout = 2;
        Db::startTrans();
        try{
            //获取用户当前账户信息
            $userinfo = self::getUserInfo($uid);
            $ori_usermoney = $userinfo['usermoney'];
            $ori_freezemoney = $userinfo['freezemoney'];
            $ori_depositmoney = $userinfo['depositmoney'];

            //重新计算余额信息
            $usermoney = $ori_usermoney;
            $freezemoney = $ori_freezemoney;
            $depositmoney = $ori_depositmoney;
            switch($tradetype){
                case 2001:
                case 2101:
                    //押金退款冻结
                    //押金退款(解冻扣款)
                    $depositmoney -= $money;
                    break;
                case 2002:
                case 2102:
                    //订单支付冻结
                    //订单支付(解冻扣款)
                    $usermoney -= $money;
                    break;
            }
            if($usermoney < 0 || $freezemoney < 0 || $depositmoney < 0){
                exception('账户余额不能小于0');
            }

            //入账
            $data_info = array(
                'f_usermoney' => $usermoney,
                'f_freezemoney' => $freezemoney,
                'f_depositmoney' => $depositmoney,
            );
            Db::name($table_userinfo)
                ->where('f_uid',$uid)
                ->update($data_info);

            //记录账户流水
            $tradenote = $this->tradetype_config[$tradetype];
            $data_paylog = array(
                'f_uid' => $uid,
                'f_inout' => $inout,
                'f_trademoney' => $money,
                'f_tradetype' => $tradetype,
                'f_suborder' => $orderid,
                'f_tradenote' => $tradenote,
            );
            Db::name($talbe_paylog)
                ->insert($data_paylog);
            Db::commit();
            return true;
        }catch (Exception $e){
            Db::rollback();
            return false;
        }
    }

    /**
     * 获取用户充值订单信息
     * @param $orderid
     * @return array|false|\PDOStatement|string|Model
     */
    public function getRechargeOrderInfo($orderid){
        $table_name = 'user_recharge_order';
        $orderinfo = Db::name($table_name)
            ->where('f_id',$orderid)
            ->field('f_uid as uid')
            ->field('f_paymoney as paymoney')
            ->field('f_paytype as paytype')
            ->field('f_channel as channel')
            ->field('f_bankmoney as bankmoney')
            ->field('f_account as account')
            ->field('f_status as status')
            ->find();
        return $orderinfo;
    }

    /**
     * 获取用户提款订单信息
     * @param $orderid
     * @return array|false|\PDOStatement|string|Model
     */
    public function getDrawOrderInfo($orderid){
        $table_name = 'user_draw_order';
        $orderinfo = Db::name($table_name)
            ->where('f_id',$orderid)
            ->field('f_uid as uid')
            ->field('f_drawmoney as drawmoney')
            ->field('f_drawtype as drawtype')
            ->field('f_channel as channel')
            ->field('f_status as status')
            ->find();
        return $orderinfo;
    }

    /**
     * 充值成功处理
     * @param $orderid
     * @param $bankorderid
     * @param $bankmoney
     * @param $account
     * @param $paynote
     * @return bool
     */
    public function rechargeSuc($orderid, $bankorderid, $bankmoney, $account, $paynote){
        $orderinfo = self::getRechargeOrderInfo($orderid);
        if(empty($orderinfo)){
            return false;
        }
        $uid = $orderinfo['uid'];
        $paymoney = $orderinfo['paymoney'];
        $paytype = $orderinfo['paytype'];
        $ori_status = $orderinfo['status'];
        if($paymoney != $bankmoney || $ori_status != 0){
            return false;
        }
        $retup = self::updateRechargeOrderStatus($orderid,$this->paysuc,$bankorderid,$bankmoney,$account,$paynote);
        if($retup){
            //存款
            return self::deposit($uid,$bankmoney,$paytype,$orderid);
        }
        return false;
    }

    /**
     * 充值失败处理
     * @param $orderid
     * @param $account
     * @param $paynote
     * @return bool
     */
    public function rechargeFail($orderid, $account, $paynote){
        $orderinfo = self::getRechargeOrderInfo($orderid);
        if(empty($orderinfo)){
            return false;
        }
        $ori_status = $orderinfo['status'];
        $ori_bankorderid = $orderinfo['bankorderid'];
        $ori_bankmoney = $orderinfo['bankmoney'];
        if($ori_status != 0){
            return false;
        }
        return self::updateRechargeOrderStatus($orderid,$this->payfail,$ori_bankorderid,$ori_bankmoney,$account,$paynote);
    }

    /**
     * 提款成功处理
     * @param $orderid
     * @param $bankorderid
     * @param $bankmoney
     * @param $account
     * @param $drawnote
     * @return bool
     */
    public function drawSuc($orderid, $bankorderid, $bankmoney, $account, $drawnote){
        $orderinfo = self::getDrawOrderInfo($orderid);
        if(empty($orderinfo)){
            return false;
        }
        $uid = $orderinfo['uid'];
        $drawmoney = $orderinfo['drawmoney'];
        $drawtype = $orderinfo['drawtype'];
        $ori_status = $orderinfo['status'];
        if($drawmoney != $bankmoney || $ori_status != 0){
            return false;
        }
        $tradetype = -1;
        if($drawtype == 200){
            $tradetype = 2101;
        }
        $retup = self::updateDrawOrderStatus($orderid,$this->drawsuc,$bankorderid,$bankmoney,$account,$drawnote);
        if($retup){
            //存款
            return self::deduct($uid,$bankmoney,$tradetype,$orderid);
        }
        return false;
    }

    /**
     * 提款失败处理
     * @param $orderid
     * @param $account
     * @param $drawynote
     * @return bool
     */
    public function drawFail($orderid, $account, $drawynote){
        $orderinfo = self::getDrawOrderInfo($orderid);
        if(empty($orderinfo)){
            return false;
        }
        $ori_status = $orderinfo['status'];
        $ori_bankorderid = $orderinfo['bankorderid'];
        $ori_bankmoney = $orderinfo['bankmoney'];
        if($ori_status != 0){
            return false;
        }
        return self::updateDrawOrderStatus($orderid,$this->drawfail,$ori_bankorderid,$ori_bankmoney,$account,$drawynote);
    }

}