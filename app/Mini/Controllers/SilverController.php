<?php
namespace App\Mini\Controllers;

use App\Models\Silver;
use App\Tools\M3Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * 银币 控制器
 * Class GoldController
 * @package App\Mini\Controllers
 */
class SilverController extends Controller
{

    /**
     * Api 银币商品列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        /*初始化*/
        $silver = new Silver();
        $m3result = new M3Result();

        $list = $silver->getGoodsList();
        /*数据过滤*/
        $list->transform(function ($item)
        {
            $item = $item->only('id', 'title', 'point', 'sort', 'first_photo');
            return $item;
        });

        $m3result->code = 0;
        $m3result->messages = '商品列表获取成功';
        $m3result->data = $list;

        return $m3result->toJson();
    }


    /**
     * Api 银币商品详情
     * @param Request $request
     * @return \App\Tools\json
     */
    public function info(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $silver = new Silver();

        /*验证*/
        $rules = [
            'id' => 'required|exists:silver_goods,id',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $info = $silver->getGoodsInfo($request->input('id'));
            $m3result->code = 0;
            $m3result->messages = '商品详情获取成功';
            $m3result->data = $info;
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '商品不存在';
        }
        return $m3result->toJson();
    }

    /**
     * 银币商品兑换
     * @param Request $request
     * @return \App\Tools\json
     */
    public function exchange(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $silver = new Silver();

        /*验证*/
        $rules = [
            'id' => 'required|exists:silver_goods,id',
            'name' => 'required',
            'address' => 'required',
            'phone' => [
                'required',
                'numeric',
                'regex:/^((1[3,5,8][0-9])|(14[5,7])|(17[0,6,7,8])|(19[7]))\d{8}$/',
            ],
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes() && $silver->exchangeGoods($request->input('id'), $request->input('name'), $request->input('phone'), $request->input('address')))
        {
            $m3result->code = 0;
            $m3result->messages = '商品兑换成功';
        }
        else
        {
            if ($silver->messages()['code'] != 0)
            {
                $m3result->code = $silver->messages()['code'];
                $m3result->messages = $silver->messages()['messages'];
            }
            else
            {
                $m3result->code = 1;
                $m3result->messages = '数据验证失败';
                $m3result->data = $validator->messages();
            }
        }
        return $m3result->toJson();
    }


}