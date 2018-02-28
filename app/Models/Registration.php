<?php

namespace App\Models;

use App\Entity\MatchRegistration;
use App\Tools\MyHelper;

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
     * 获取所有订单列表 (如有where 则加入新的sql条件) "分页" | 默认排序:创建时间
     * @param array $where
     * @param array $orderBy
     * @param bool $is_paginate
     * @param bool $is_whereIn
     * @param null $whereIn
     * @return mixed
     */
    public function getRegistrationList($where = array(), $orderBy = array(['match_registration.create_time', 'desc']), $is_paginate = true, $is_whereIn = false, $whereIn = null)
    {
        /*初始化*/
        $e_match_registration = new MatchRegistration();

        /*预加载ORM对象*/
        $e_match_registration = $e_match_registration->where($where)->with('match_info');
        foreach ($orderBy as $value)
        {
            $e_match_registration->orderBy($value[0], $value[1]);
        }
        /*是否使用whereIn*/
        if ($is_whereIn === true)
        {
            $e_match_registration->whereIn($whereIn[0], $whereIn[1]);
        }

        /*是否需要分页*/
        if ($is_paginate === true)
        {
            $e_match_registration = $e_match_registration->paginate($_COOKIE['PaginationSize']);
        }
        else
        {
            $e_match_registration = $e_match_registration->get();
        }

        /*数据过滤*/
        $e_match_registration->transform(function ($item)
        {
            $item->status_text = self::statusTransformText($item->status);
            $item->match_info->need_money = MyHelper::money_format($item->match_info->need_money);
            $item->match_info->status_text = Match::statusTransformText($item->match_info->status);
            $item->match_info->first_photo = $item->match_info->match_photos[0] != null ? MyFile::makeUrl($item->match_info->match_photos[0]) : null;
            return $item;
        });

        return $e_match_registration;
    }

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