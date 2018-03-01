<?php

namespace App\Models;

use App\Entity\MatchList;
use App\Tools\MyHelper;
use Illuminate\Support\Facades\DB;

/**
 * Class Match 赛事相关模型
 * @package App\Models
 */
class Match extends Model
{
    /*删除状态:  1.已删除  0.正常*/
    const IS_DELETE = 1;
    const NO_DELETE = 0;

    /*赛事状态:  0.报名中  100.抽号中  200.已结束*/
    const STATUS_SIGN_UP    = 0;
    const STATUS_GET_NUMBER = 100;
    const STATUS_END        = 200;

    /**
     * 获取所有比赛列表 (如有where 则加入新的sql条件) "分页" | 默认排序:创建时间
     * @param array $where
     * @param array $orderBy
     * @param bool $is_paginate
     * @param bool $is_whereIn
     * @param null $whereIn
     * @return mixed
     */
    public function getMatchList($where = array(), $orderBy = array(['match_list.create_time', 'desc']), $is_paginate = true, $is_whereIn = false, $whereIn = null)
    {
        /*初始化*/
        $e_match_list = new MatchList();

        /*预加载ORM对象*/
        $e_match_list = $e_match_list->where($where);
        foreach ($orderBy as $value)
        {
            $e_match_list->orderBy($value[0], $value[1]);
        }
        /*是否使用whereIn*/
        if ($is_whereIn === true)
        {
            $e_match_list->whereIn($whereIn[0], $whereIn[1]);
        }

        /*是否需要分页*/
        if ($is_paginate === true)
        {
            $match_list = $e_match_list->paginate($_COOKIE['PaginationSize']);
        }
        else
        {
            $match_list = $e_match_list->get();
        }

        /*数据过滤*/
        $match_list->transform(function ($item)
        {
            $item->need_money = MyHelper::money_format($item->need_money);
            $item->status_text = self::statusTransformText($item->status);
            return $item;
        });

        return $match_list;
    }

    /**
     * 获取单个比赛详情
     * @param $match_id
     * @return mixed
     */
    public function getMatchInfo($match_id)
    {
        /*初始化*/
        $e_match_list = MatchList::findOrFail($match_id);
        $url_photos = array();

        /*数据过滤*/
        $e_match_list->need_money = MyHelper::money_format($e_match_list->need_money);
        $e_match_list->status_text = self::statusTransformText($e_match_list->status);
        $e_match_list->registration_sum_number = $e_match_list->reg_list()->count();

        foreach ($e_match_list->match_photos as $key => $value)
        {
            $url_photos[] = MyFile::makeUrl($value);
        }
        $e_match_list->url_photos = $url_photos;
        return $e_match_list;
    }

    /**
     * 发布一场比赛
     * @param $arr
     * @return bool
     * @throws \Throwable
     */
    public function releaseMatch($arr)
    {
        /*事物*/
        try
        {
            $address = new Location();
            $address_res = $address->tencent_coordinateAddressResolution($arr['address_coordinate_lat'], $arr['address_coordinate_lng']);

            DB::transaction(function () use ($arr, $address, $address_res)
            {
                /*初始化*/
                $session_user = session('User');
                $e_match_list = new MatchList();
                $e_match_address = $address->getMatchAddressFromCity($address_res['result']['address_component']['city']);

                /*如地址不存在新增地址*/
                if ($e_match_address == null)
                {
                    $e_match_address = $address->addMatchAddress($address_res['result']['address_component']['province'], $address_res['result']['address_component']['city'], $address_res['result']['address_component']['district']);
                }

                /*添加*/
                $e_match_list->user_id = $session_user->user_id;
                $e_match_list->status = self::STATUS_SIGN_UP;
                $e_match_list->title = $arr['title'];
                $e_match_list->need_money = $arr['need_money'];
                $e_match_list->hotline = $arr['hotline'];
                $e_match_list->address_id = $e_match_address->address_id;
                $e_match_list->address_name = $arr['address_name'];
                $e_match_list->address_coordinate = ['lat' => $arr['address_coordinate_lat'], 'lng' => $arr['address_coordinate_lng']];
                $e_match_list->match_start_time = $arr['match_start_time'];
                $e_match_list->match_end_time = $arr['match_end_time'];
                $e_match_list->match_start_number = $arr['match_start_number'];
                $e_match_list->match_end_number = $arr['match_end_number'];
                $e_match_list->match_sum_number = bcadd(bcsub($arr['match_end_number'], $arr['match_start_number']), 1);
                $e_match_list->match_content = $arr['match_content'];
                $e_match_list->match_service = $arr['match_service'];
                $e_match_list->match_photos = explode(',', $arr['match_photos']);
                $e_match_list->fish_number = $arr['fish_number'];
                $e_match_list->is_delete = self::NO_DELETE;
                $e_match_list->create_time = now();

                $e_match_list->save();
            });
        } catch (\Exception $e)
        {
            $this->errors['code'] = 1;
            $this->errors['messages'] = '比赛发布失败';
            return false;
        }
        return true;
    }

    /**
     * 删除一场比赛(伪删除)
     * @param $id
     * @return bool
     * @throws \Throwable
     */
    public function deleteMatch($id)
    {
        /*事物*/
        try
        {
            DB::transaction(function () use ($id)
            {
                $e_match_list = MatchList::lockForUpdate()->find($id);
                /*伪删除*/
                $e_match_list->is_delete = self::IS_DELETE;
                $e_match_list->save();
            });
        } catch (\Exception $e)
        {
            $this->errors['code'] = 1;
            $this->errors['messages'] = '比赛删除失败';
            return false;
        }
        return true;
    }

    /**
     * 返回赛事状态 的文本名称
     * @param $status
     * @return string
     */
    public static function statusTransformText($status)
    {
        $text = '';
        switch ($status)
        {
            case self::STATUS_SIGN_UP:
                $text = '报名中';
                break;
            case self::STATUS_GET_NUMBER:
                $text = '抽号中';
                break;
            case self::STATUS_END:
                $text = '已结束';
                break;
        }
        return $text;
    }

    /**
     * 返回赛事删除状态 的文本名称
     * @param $is_disable
     * @return string
     */
    public static function isDeleteTransformText($is_disable)
    {
        $text = '';
        switch ($is_disable)
        {
            case self::IS_DELETE:
                $text = '已删除';
                break;
            case self::NO_DELETE:
                $text = '正常';
                break;
        }
        return $text;
    }

}