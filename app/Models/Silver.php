<?php
namespace App\Models;

use App\Entity\SilverExchange;
use App\Entity\SilverGoods;
use App\Entity\Users;
use Illuminate\Support\Facades\DB;

/**
 * Class Silver 银币相关模型
 * @package App\Models
 */
class Silver extends Model
{
    /*兑换申请状态 10.待兑换  20.兑换成功*/
    const EXCHANGE_STATUS_WAIT = 10;
    const EXCHANGE_STATUS_SUCCESS = 20;

    /**
     * 获取所有银币商品列表 (如有where 则加入新的sql条件) "分页" | 默认排序:创建时间
     * @param array $where
     * @param array $orderBy
     * @param bool $is_paginate
     * @param bool $is_whereIn
     * @param null $whereIn
     * @return mixed
     */
    public function getGoodsList($where = array(), $orderBy = array(['silver_goods.sort', 'desc']), $is_paginate = true, $is_whereIn = false, $whereIn = null)
    {
        /*初始化*/
        $e_silver_goods = new SilverGoods();

        /*预加载ORM对象*/
        $e_silver_goods = $e_silver_goods->where($where);
        foreach ($orderBy as $value)
        {
            $e_silver_goods->orderBy($value[0], $value[1]);
        }
        /*是否使用whereIn*/
        if ($is_whereIn === true)
        {
            $e_silver_goods->whereIn($whereIn[0], $whereIn[1]);
        }

        /*是否需要分页*/
        if ($is_paginate === true)
        {
            $list = $e_silver_goods->paginate($_COOKIE['PaginationSize']);
        }
        else
        {
            $list = $e_silver_goods->get();
        }

        /*数据过滤*/
        $list->transform(function ($item)
        {
            $item->first_photo = $item->photos[0] != null ? MyFile::makeUrl($item->photos[0]) : null;
            return $item;
        });

        return $list;
    }

    /**
     * 获取单个银币商品详情
     * @param $id
     * @return array
     */
    public function getGoodsInfo($id)
    {
        /*初始化*/
        $e_silver_goods = SilverGoods::findOrFail($id);
        $url_photos = array();

        /*数据过滤*/
        foreach ($e_silver_goods->photos as $key => $value)
        {
            $url_photos[] = MyFile::makeUrl($value);
        }
        $e_silver_goods->url_photos = $url_photos;
        return $e_silver_goods;
    }

    /**
     * 用户兑换一件积分商品
     * @param $goods_id
     * @param $name
     * @param $phone
     * @param $address
     * @return bool
     */
    public function exchangeGoods($goods_id, $name, $phone, $address)
    {
        $session_user = session('User');

        /*事物*/
        try
        {
            DB::transaction(function () use ($session_user, $goods_id, $name, $phone, $address)
            {
                $e_users = Users::findOrFail($session_user->user_id);
                $e_silver_goods = SilverGoods::findOrFail($goods_id);

                if ($e_users->silver_coin < $e_silver_goods->point)
                {
                    throw new \Exception('用户银币不足', 10);
                }

                $silver_exchange = new SilverExchange();
                $silver_exchange->user_id = $e_users->user_id;
                $silver_exchange->goods_id = $e_silver_goods->id;
                $silver_exchange->status = self::EXCHANGE_STATUS_WAIT;
                $silver_exchange->name = $name;
                $silver_exchange->phone = $phone;
                $silver_exchange->address = $address;
                $silver_exchange->save();

                $e_users->silver_coin = bcsub($e_users->silver_coin, $e_silver_goods->point);
                $e_users->save();

                Transaction::silverLogChange($e_users->user_id, Transaction::SILVER_LOG_TYPE_EXCHANGE, bcsub(0, $e_silver_goods->point));
            });
        } catch (\Exception $e)
        {
            $this->errors['code'] = $e->getCode();
            $this->errors['messages'] = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * 返回兑换状态 的文本名称
     * @param $status
     * @return string
     */
    public static function exchangeStatusTransformText($status)
    {
        $text = '';
        switch ($status)
        {
            case self::EXCHANGE_STATUS_WAIT:
                $text = '待兑换';
                break;
            case self::EXCHANGE_STATUS_SUCCESS:
                $text = '兑换成功';
                break;
        }
        return $text;
    }

    /**
     * 同意兑换
     * @param $id
     * @return bool
     * @throws \Throwable
     */
    public function agreeExchange($id)
    {
        /*事物*/
        try
        {
            DB::transaction(function () use ($id)
            {
                $e_silver_exchange = SilverExchange::lockForUpdate()->where('id',$id)->where('status',self::EXCHANGE_STATUS_WAIT)->firstOrFail();

                $e_silver_exchange->status = self::EXCHANGE_STATUS_SUCCESS;
                $e_silver_exchange->save();
            });
        } catch (\Exception $e)
        {
            $this->errors['code'] = 1;
            $this->errors['messages'] = $e->getMessage();
            return false;
        }
        return true;
    }
}