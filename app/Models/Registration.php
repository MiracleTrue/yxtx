<?php

namespace App\Models;

use App\Entity\MatchList;
use App\Entity\MatchRegistration;
use App\Exceptions\NetworkBusyException;
use App\Tools\MyHelper;
use Illuminate\Support\Facades\DB;

/**
 * Class Registration 报名相关模型
 * @package App\Models
 */
class Registration extends Model
{
    /*报名状态: 0.待支付  10.未抽号  20.已抽号*/
    const STATUS_WAIT_PAYMENT = 0;
    const STATUS_WAIT_NUMBER = 10;
    const STATUS_ALREADY_NUMBER = 20;

    /*报名方式: 10.微信支付  20.现金报名*/
    const TYPE_WECHAT = 10;
    const TYPE_CASH = 20;

    /**
     * 为一个报名比赛的用户,抽取比赛号码
     * @param $user_id
     * @param $match_id
     * @return bool|null
     */
    public function getNumber($user_id, $match_id)
    {
        /*初始化*/
        $return_entity = null;
        $arr = array();

        /*事物*/
        try
        {
            DB::transaction(function () use ($match_id, $user_id, $arr, &$return_entity)
            {
                $e_match_list = MatchList::where('match_id', $match_id)->where('status', Match::STATUS_GET_NUMBER)->lockForUpdate()->first();

                if ($e_match_list == null)
                {
                    throw new NetworkBusyException();
                }

                for ($i = $e_match_list->match_start_number; $i <= $e_match_list->match_end_number; $i++)
                {
                    $arr[] = $i;
                }
                $reg_numbers = $e_match_list->reg_list->where('status', self::STATUS_ALREADY_NUMBER)->pluck('match_number');

                $rd_numbers = collect($arr)->diff($reg_numbers);/*可随机的号码*/

                /*抽号*/
                $e_match_registration = MatchRegistration::where('user_id', $user_id)->where('match_id', $match_id)->first();
                $e_match_registration->status = self::STATUS_ALREADY_NUMBER;
                $e_match_registration->match_number = $rd_numbers->random();
                $e_match_registration->save();
                $return_entity = $e_match_registration;
            });
        } catch (\Exception $e)
        {
            $this->errors['code'] = 1;
            $this->errors['messages'] = '网络繁忙';
            return false;
        }
        return $return_entity;
    }

    /**
     * 获取单个报名详情
     * @param $reg_id
     * @return mixed
     */
    public function getRegistrationInfo($reg_id)
    {
        /*初始化*/
        $e_match_registration = MatchRegistration::findOrFail($reg_id);
        $url_photos = array();

        /*数据过滤*/
        $e_match_registration->match_info = $e_match_registration->match_info;

        $e_match_registration->match_info->need_money = MyHelper::money_format($e_match_registration->match_info->need_money);
        $e_match_registration->match_info->status_text = self::statusTransformText($e_match_registration->match_info->status);
        $e_match_registration->match_info->registration_sum_number = $e_match_registration->match_info->reg_list()->count();
        $e_match_registration->match_info->address_info = $e_match_registration->match_info->address_info;

        foreach ($e_match_registration->match_info->match_photos as $key => $value)
        {
            $url_photos[] = MyFile::makeUrl($value);
        }
        $e_match_registration->match_info->url_photos = $url_photos;

        return $e_match_registration;
    }

    /**
     * 单个用户报名参加一场比赛
     * @param $user_id
     * @param $match_id
     * @param $real_name
     * @return bool|null
     */
    public function registrationMatch($user_id, $match_id, $real_name = '')
    {
        /*初始化*/
        $return_entity = null;

        /*事物*/
        try
        {
            DB::transaction(function () use ($match_id, $user_id, $real_name, &$return_entity)
            {
                $e_match_list = MatchList::where('match_id', $match_id)->whereIn('status', [Match::STATUS_SIGN_UP, Match::STATUS_GET_NUMBER])->where('match_end_time', '>', now())->lockForUpdate()->first();
                $e_match_list->registration_sum_number = $e_match_list->reg_list()->count();

                if ($e_match_list == null)
                {
                    throw new NetworkBusyException();
                }

                if ($e_match_list->registration_sum_number < $e_match_list->match_sum_number)
                {
                    $e_match_registration = new MatchRegistration();
                    $e_match_registration->user_id = $user_id;
                    $e_match_registration->match_id = $e_match_list->match_id;
                    $e_match_registration->order_sn = $this->makeOrderSn();
                    $e_match_registration->status = self::STATUS_WAIT_PAYMENT;
                    $e_match_registration->real_name = $real_name;
                    $e_match_registration->match_number = null;
                    $e_match_registration->create_time = now();
                    $e_match_registration->save();

                    $return_entity = $e_match_registration;
                }
                else
                {
                    throw new \Exception('该比赛报名人数已满');
                }
            });
        } catch (\Exception $e)
        {
            $this->errors['code'] = 1;
            $this->errors['messages'] = '该比赛报名人数已满';
            return false;
        }
        return $return_entity;
    }

    /**
     * 获取所有报名列表 (如有where 则加入新的sql条件) "分页" | 默认排序:创建时间
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
            $item->type_text = self::typeTransformText($item->type);
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

    /**
     * 返回报名方式 的文本名称
     * @param $type
     * @return string
     */
    public static function typeTransformText($type)
    {
        $text = '';
        switch ($type)
        {
            case self::TYPE_WECHAT:
                $text = '微信支付';
                break;
            case self::TYPE_CASH:
                $text = '现金报名';
                break;
        }
        return $text;
    }

}