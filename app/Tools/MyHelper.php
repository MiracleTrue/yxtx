<?php
namespace App\Tools;

/**
 * 我的帮助函数
 * Class MyHelper
 * @package App\Tools
 */
class MyHelper
{
    /**
     * 判断是否是Unix 时间戳
     * @param $timestamp
     * @return bool
     */
    public static function is_timestamp($timestamp)
    {
        if (strtotime(date('YmdHis', intval($timestamp))) === $timestamp)
        {
            return true;
        }
        else return false;
    }
}