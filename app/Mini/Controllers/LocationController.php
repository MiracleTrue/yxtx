<?php
namespace App\Mini\Controllers;

use App\Entity\MatchAddress;
use App\Models\Location;
use App\Tools\M3Result;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Api 获取服务开通城市
     * @param Request $request
     * @return \App\Tools\json
     */
    public function serviceCity(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $location = new Location();

        $e_match_address = MatchAddress::where('in_service', Location::MATCH_ADDRESS_IS_SERVICE)->get();

        /*数据过滤*/
        $e_match_address->transform(function ($item) use ($location)
        {
            $item->city_simple = $location->cityToSimple($item->city);
            $item = $item->only('province', 'city', 'city_simple');
            return $item;
        });

        /*返回*/
        $m3result->code = 0;
        $m3result->messages = '服务开通城市列表';
        $m3result->data = $e_match_address;
        return $m3result->toJson();
    }

}