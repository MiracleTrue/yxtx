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

    /**
     * 判断是否该保留xx金额后的.00
     * @param $money
     * @return int
     */
    public static function money_format($money)
    {
        $int_money = intval($money);

        if($int_money == $money)
        {
            return $int_money;
        }
        else
        {
            return $money;
        }
    }
}