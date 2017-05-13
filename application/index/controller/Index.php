<?php
namespace app\index\controller;

class Index
{
    public function index()
    {
        //var_dump(\think\Env::get('baseurl'));
        //return 'hello world';
        $levelinfo = array(23,24,25,26);
        foreach(array_slice($levelinfo,1,99,true) as $k=>$pid){
            var_dump($k,$pid);
        }

    }
}
