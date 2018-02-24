<?php
namespace App\Models;

/**
 * Class Registration 报名相关模型
 * @package App\Models
 */
class Registration extends Model
{
    /*报名状态:  10.未抽号  20.已抽号*/
    const STATUS_WAIT_NUMBER    = 10;
    const STATUS_ALREADY_NUMBER = 20;

    /**
     * 返回报名状态 的文本名称
     * @param $status
     * @return string
     */
    public static function statusTransformText($status)
    {
        $text = '';
        switch ($status)
        {
            case self::STATUS_WAIT_NUMBER:
                $text = '未抽号';
                break;
            case self::STATUS_ALREADY_NUMBER:
                $text = '已抽号';
                break;
        }
        return $text;
    }

}