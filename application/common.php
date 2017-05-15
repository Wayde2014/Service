<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 验证手机号码正确性
 * @param $mobile
 * @return bool
 */
function check_mobile($mobile)
{
    if (!is_numeric($mobile)) {
        return false;
    }
    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
}

/**
 * 检测身份证号码是否合法
 * @param $idcode
 * @return bool
 */
function check_idcode($idcode)
{
    if (preg_match('/^[0-9a-zA-Z]{15,18}$/D', $idcode)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 检测时间格式
 * @param $datetime
 * @param string $formate
 * @return bool
 */
function check_datetime($datetime, $formate = 'yyyy-mm-dd hh:ii:ss')
{
    $matchstr = '/^'.$formate.'$/s';
    $matchstr = preg_replace('/yyyy/i', '\d{4}', $matchstr);
    $matchstr = preg_replace('/mm|dd|hh|ii|ss/i', '\d{2}', $matchstr);
    if (preg_match($matchstr, $datetime)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 检测用户名格式
 * @param $str
 * @return bool
 */
function checkUserName($str)
{
    if (strlen($str) == 0 || is_null($str))
    {
        return false;
    }
    //输入的数据必须是英文和数字
    $pattern = "/^([A-Z|a-z|0-9])+$/";
    if (! preg_match($pattern, $str)){
        return false;
    }
    return true;
}