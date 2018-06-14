<?php
namespace App\Mini\Controllers;
use App\Models\Gold;
use App\Tools\M3Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


/**
 * 金币 控制器
 * Class GoldController
 * @package App\Mini\Controllers
 */
class GoldController extends Controller
{

    /**
     * Api 金币商品列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        /*初始化*/
        $gold = new Gold();
        $m3result = new M3Result();

        $list = $gold->getGoodsList();
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
     * Api 金币商品详情
     * @param Request $request
     * @return \App\Tools\json
     */
    public function info(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $gold = new Gold();

        /*验证*/
        $rules = [
            'id' => 'required|exists:gold_goods,id',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $info = $gold->getGoodsInfo($request->input('id'));
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


}