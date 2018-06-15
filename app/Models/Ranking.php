<?php

namespace App\Models;

use App\Entity\PitRanking;
use App\Entity\Users;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


/**
 * Class Ranking 排行相关模型
 * @package App\Models
 */
class Ranking extends Model
{

    /**
     * 获取所有坑冠比赛列表 (如有where 则加入新的sql条件) "分页" | 默认排序:创建时间
     * @param array $where
     * @param array $orderBy
     * @param bool $is_paginate
     * @param bool $is_whereIn
     * @param null $whereIn
     * @return mixed
     */
    public function getPitList($where = array(), $orderBy = array(['pit_ranking.create_time', 'desc']), $is_paginate = true, $is_whereIn = false, $whereIn = null)
    {
        /*初始化*/
        $e_match_list = new PitRanking();

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
            $item->first_photo = $item->match_photos[0] != null ? MyFile::makeUrl($item->match_photos[0]) : null;
            return $item;
        });

        return $match_list;
    }

    /**
     * 获取单个用户坑冠排名
     * @param $user_id
     * @return string
     */
    public function getOneUserPitRanking($user_id)
    {
        $e_users = Users::orderBy('pit_release_count', 'desc')->limit(100)->get();

        $find_user = $e_users->where('user_id', $user_id);

        if ($find_user->isEmpty())
        {
            return '100+';
        }
        else
        {
            return bcadd($find_user->keys()->first(), 1);
        }
    }

    /**
     * 获取单个用户钓场排名
     * @param $user_id
     * @return string
     */
    public function getOneUserMatchRanking($user_id)
    {
        $e_users = Users::orderBy('match_release_count', 'desc')->limit(100)->get();

        $find_user = $e_users->where('user_id', $user_id);

        if ($find_user->isEmpty())
        {
            return '100+';
        }
        else
        {
            return bcadd($find_user->keys()->first(), 1);
        }
    }

    /**
     * 获取单个坑冠比赛详情
     * @param $id
     * @return mixed
     */
    public function getPitInfo($id)
    {
        /*初始化*/
        $e_pit_ranking = PitRanking::findOrFail($id);
        $url_photos = array();

        /*数据过滤*/
        $e_pit_ranking->address_info = $e_pit_ranking->address_info;

        foreach ($e_pit_ranking->match_photos as $key => $value)
        {
            $url_photos[] = MyFile::makeUrl($value);
        }
        $e_pit_ranking->url_photos = $url_photos;
        return $e_pit_ranking;
    }

    /**
     * 发布一场坑冠比赛
     * @param $arr
     * @return bool
     * @throws \Throwable
     */
    public function releasePit($arr)
    {
        /*事物*/
        try
        {
            $location = new Location();

            DB::transaction(function () use ($arr, $location)
            {
                /*初始化*/
                $session_user = session('User');
                $e_pit_Ranking = new PitRanking();
                $e_users = Users::findOrFail($session_user->user_id);


                if (PitRanking::where('user_id', $session_user->user_id)->whereBetween('create_time', [Carbon::today(), Carbon::tomorrow()])->first() != null)
                {
                    throw new \Exception('今日已发布比赛', 2);
                }

                if ($e_users->pit_remain_number <= 0)
                {
                    throw new \Exception('发布次数不足', 3);
                }


                $e_match_address = $location->getMatchAddressFromCity($session_user->location);

                /*添加*/
                $e_pit_Ranking->user_id = $session_user->user_id;
                $e_pit_Ranking->address_id = $e_match_address->address_id;
                $e_pit_Ranking->title = $arr['title'];
                $e_pit_Ranking->fish_number = $arr['fish_number'];
                $e_pit_Ranking->address_name = $arr['address_name'];
                $e_pit_Ranking->match_time = $arr['match_time'];
                $e_pit_Ranking->match_photos = explode(',', $arr['match_photos']);
                $e_pit_Ranking->create_time = now();
                $e_pit_Ranking->save();

                $e_users->pit_release_count = bcadd($e_users->pit_release_count, 1);
                $e_users->gold_coin = bcadd($e_users->gold_coin, 1);
                $e_users->pit_remain_number = bcsub($e_users->pit_remain_number, 1);
                $e_users->save();
                Transaction::goldLogChange($e_users->user_id, Transaction::GOLD_LOG_TYPE_RELEASE_PIT, 1);
            });
        } catch (\Exception $e)
        {
            if ($e->getCode() == 2)
            {
                $this->errors['code'] = 2;
                $this->errors['messages'] = $e->getMessage();
            }
            elseif ($e->getCode() == 3)
            {
                $this->errors['code'] = 3;
                $this->errors['messages'] = $e->getMessage();
            }
            else
            {
                $this->errors['code'] = 1;
                $this->errors['messages'] = '系统错误';
            }

            return false;
        }
        return true;
    }


}