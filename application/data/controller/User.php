<?php
namespace app\data\controller;

class User
{
    public function Index()
    {
        return json(['data'=>$res,'code'=>1,'message'=>'操作完成']);
    }
    public function sendsms(){
        $Sms = new \third\Sms();
        return $Sms -> sendsms('13812345678');
    }
    public function checksms(){
        $Sms = new \third\Sms();
        return $Sms->checksms('13812345678', 1234);
    }
}
