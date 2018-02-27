<?php
namespace App\Models;

use App\Entity\MatchList;
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
    const STATUS_SIGN_UP = 0;
    const STATUS_GET_NUMBER = 100;
    const STATUS_END = 200;

    public function getMatchList($where = array(), $orderBy = array(['match_list.create_time', 'desc']), $is_paginate = true)
    {
        /*初始化*/
        $e_match_list = new MatchList();

        /*预加载ORM对象*/
        $e_match_list = $e_match_list->where($where);
        foreach ($orderBy as $value)
        {
            $e_match_list->orderBy($value[0], $value[1]);
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

        return $match_list;
    }

    /**
     * 获取单个比赛详情
     * @param $match_id
     * @return mixed
     */
    public function getMatchInfo($match_id)
    {
        $e_match_list = MatchList::findOrFail($match_id);

        return $e_match_list;
    }

    /**
     * 发布一场比赛
     * @param $arr
     * @return bool
     */
    public function releaseMatch($arr)
    {
        /*事物*/
        try
        {
            $address = new Address();
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