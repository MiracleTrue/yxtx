<?php
namespace App\Mini\Controllers;

use App\Entity\BannerList;
use App\Entity\MatchAddress;
use App\Models\Match;
use App\Models\MyFile;
use App\Tools\M3Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * 首页 控制器
 * Class IndexController
 * @package App\Mini\Controllers
 */
class IndexController extends Controller
{
    /**
     * Api 搜索比赛列表
     * @param Request $request
     * @return \App\Tools\json
     */
    public function search(Request $request)
    {
        /*初始化*/
        $match = new Match();
        $m3result = new M3Result();
        $my_file = new MyFile();

        /*验证*/
        $rules = [
            'keyword' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $list = $match->getMatchList([['title', 'like', '%' . $request->input('keyword') . '%']]);
            /*数据过滤*/
            $list->transform(function ($item) use ($my_file)
            {
                $item = $item->only('match_id', 'title', 'status', 'status_text', 'address_name', 'match_start_time', 'match_end_time', 'match_sum_number', 'fish_number', 'need_money', 'first_photo');
                return $item;
            });

            $m3result->code = 0;
            $m3result->messages = '比赛列表获取成功';
            $m3result->keyword = $request->input('keyword');
            $m3result->data = $list;
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '请输入关键字';
        }
        return $m3result->toJson();
    }


    /**
     * Api 首页banner图
     * @param Request $request
     * @return \App\Tools\json
     */
    public function banner(Request $request)
    {
        /*初始化*/
        $my_file = new MyFile();
        $m3result = new M3Result();
        $e_banner_list = BannerList::orderBy('sort', 'desc')->get();

        $e_banner_list->transform(function ($item) use ($my_file)
        {
            $item->url_path = $my_file->makeUrl($item->file_path);
            return $item;
        });

        $m3result->code = 0;
        $m3result->messages = 'Banner图获取成功';
        $m3result->data = $e_banner_list->pluck('url_path');

        return $m3result->toJson();
    }

    /**
     * Api 首页比赛列表
     * @param Request $request
     * @return \App\Tools\json
     */
    public function match(Request $request)
    {
        /*初始化*/
        $match = new Match();
        $m3result = new M3Result();
        $session_user = session('User');
        $my_file = new MyFile();

        /*位置筛选*/
        if ($session_user != null && !empty($session_user->location))
        {
            $e_match_address = MatchAddress::where('city', $session_user->location)->first();
            if ($e_match_address != null)
            {
                $location_id = $e_match_address->address_id;
            }
            else
            {
                $location_id = MatchAddress::where('city', '青岛市')->first()->address_id;
            }
        }
        else
        {
            $location_id = MatchAddress::where('city', '青岛市')->first()->address_id;
        }

        $list = $match->getMatchList([['address_id', $location_id]]);
        /*数据过滤*/
        $list->transform(function ($item) use ($my_file)
        {
            $item->first_photo = $item->match_photos[0] != null ? $my_file->makeUrl($item->match_photos[0]) : null;
            $item = $item->only('match_id', 'title', 'status', 'status_text', 'address_name', 'match_start_time', 'match_end_time', 'match_sum_number', 'fish_number', 'need_money', 'first_photo');
            return $item;
        });

        $m3result->code = 0;
        $m3result->messages = '比赛列表获取成功';
        $m3result->data = $list;

        return $m3result->toJson();
    }

}