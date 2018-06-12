<?php

namespace App\Mini\Controllers;


use App\Entity\MatchAddress;
use App\Entity\MatchList;
use App\Entity\PitRanking;
use App\Entity\RankingBanner;
use App\Entity\Users;
use App\Models\Location;
use App\Models\MyFile;
use App\Models\Ranking;
use App\Tools\M3Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * 排行 控制器
 * Class IndexController
 * @package App\Mini\Controllers
 */
class RankingController extends Controller
{

    /**
     * Api 坑冠榜
     * @param Request $request
     * @return \App\Tools\json
     */
    public function pit(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();

        /*验证*/
        $rules = [
            'address_id' => 'sometimes|exists:match_address,address_id',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            if (!empty($request->input('address_id')))
            {
                $e_match_address = MatchAddress::find($request->input('address_id'));

                $list = Users::with('pit_list', 'gold_exchange')->orderBy('pit_release_count', 'desc')->where('location', $e_match_address->city)->limit(100)->get();
            }
            else
            {
                $list = Users::with('pit_list', 'gold_exchange')->orderBy('pit_release_count', 'desc')->limit(100)->get();
            }

            /*数据过滤*/
            $list->transform(function ($item)
            {
                $item->fish_count = $item->pit_list->sum('fish_number');
                $item->exchange_count = $item->gold_exchange->count();
                $item = $item->only('user_id', 'avatar', 'nick_name', 'location', 'pit_release_count', 'fish_count', 'exchange_count');
                return $item;
            });
            $m3result->code = 0;
            $m3result->messages = '坑冠榜获取成功';
            $m3result->data = $list;
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '地址错误';
        }
        return $m3result->toJson();
    }

    /**
     * Api 钓场榜
     * @param Request $request
     * @return \App\Tools\json
     */
    public function match(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();

        /*验证*/
        $rules = [
            'address_id' => 'sometimes|exists:match_address,address_id',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            if (!empty($request->input('address_id')))
            {
                $e_match_address = MatchAddress::find($request->input('address_id'));

                $list = Users::with('silver_exchange')->orderBy('match_release_count', 'desc')->where('location', $e_match_address->city)->limit(100)->get();
            }
            else
            {
                $list = Users::with('silver_exchange')->orderBy('match_release_count', 'desc')->limit(100)->get();
            }

            /*数据过滤*/
            $list->transform(function ($item)
            {
                $item->exchange_count = $item->silver_exchange->count();
                $item = $item->only('user_id', 'avatar', 'nick_name', 'location', 'match_release_count', 'exchange_count');
                return $item;
            });
            $m3result->code = 0;
            $m3result->messages = '钓场榜获取成功';
            $m3result->data = $list;
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '地址错误';
        }
        return $m3result->toJson();
    }


    /**
     * Api 分享成功增加坑冠发布次数回调
     * @param Request $request
     * @return \App\Tools\json
     */
    public function pitRemainNumber(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();

        /*验证*/
        $rules = [
            'openid' => 'required|exists:users,openid',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $m3result->code = 0;
            $m3result->messages = '分享成功';
            Users::where('openid', $request->input('openid'))->increment('pit_remain_number');
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '用户不存在';
        }
        return $m3result->toJson();
    }

    /**
     * Api 排行banner图列表
     * @param Request $request
     * @return \App\Tools\json
     */
    public function banner(Request $request)
    {
        /*初始化*/
        $my_file = new MyFile();
        $m3result = new M3Result();
        $e_banner_list = RankingBanner::orderBy('sort', 'desc')->get();

        /*数据过滤*/
        $e_banner_list->transform(function ($item) use ($my_file)
        {
            $item->url_path = $my_file->makeUrl($item->file_path);
            $item->video_path = !empty($item->video_path) ? $my_file->makeUrl($item->video_path) : '';
            $item = $item->only('banner_id', 'url_path', 'video_path');
            return $item;
        });

        $m3result->code = 0;
        $m3result->messages = 'Banner图获取成功';
        $m3result->data = $e_banner_list;

        return $m3result->toJson();
    }

    /**
     * Api 排行banner图详情
     * @param Request $request
     * @return \App\Tools\json
     */
    public function bannerDetail(Request $request)
    {
        /*初始化*/
        $my_file = new MyFile();
        $m3result = new M3Result();
        $e_banner_list = RankingBanner::findOrFail($request->input('banner_id'));

        /*数据过滤*/
        $e_banner_list->url_path = $my_file->makeUrl($e_banner_list->file_path);
        $e_banner_list->video_path = !empty($e_banner_list->video_path) ? $my_file->makeUrl($e_banner_list->video_path) : '';

        $m3result->code = 0;
        $m3result->messages = '详情获取成功';
        $m3result->data = $e_banner_list;

        return $m3result->toJson();
    }

    /**
     * Api 排行首页信息
     * @param Request $request
     * @return \App\Tools\json
     */
    public function index(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();

        $m3result->code = 0;
        $m3result->messages = '排行榜首页信息';
        $m3result->data['pit_count'] = PitRanking::count();
        $m3result->data['match_count'] = MatchList::count();

        return $m3result->toJson();
    }

    /**
     * Api 获取用户坑冠比赛列表
     * @param Request $request
     * @return \App\Tools\json
     */
    public function pitListFromUser(Request $request)
    {
        /*初始化*/
        $ranking = new Ranking();
        $m3result = new M3Result();

        /*验证*/
        $rules = [
            'user_id' => 'required|exists:users,user_id',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $list = $ranking->getPitList([['user_id', $request->input('user_id')]]);
            $m3result->code = 0;
            $m3result->messages = '坑冠列表获取成功';
            $m3result->data = $list;
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '用户不存在';
        }
        return $m3result->toJson();
    }

    /**
     * Api 获取坑冠比赛详情
     * @param Request $request
     * @return \App\Tools\json
     */
    public function info(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $ranking = new Ranking();

        /*验证*/
        $rules = [
            'id' => 'required|exists:pit_ranking,id',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $info = $ranking->getPitInfo($request->input('id'));
            $m3result->code = 0;
            $m3result->messages = '获取比赛详情成功';
            $m3result->data = $info;
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '比赛不存在';
        }
        return $m3result->toJson();
    }

    /**
     * Api 坑冠比赛发布
     * @param Request $request
     * @return \App\Tools\json
     * @throws \Throwable
     */
    public function release(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $ranking = new Ranking();

        /*验证*/
        $rules = [
            'title' => 'required',
            'address_name' => 'required',
            'match_time' => 'required|date',
            'fish_number' => 'required',
            'match_photos' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes() && $ranking->releasePit($request->all()))
        {
            $m3result->code = 0;
            $m3result->messages = '比赛发布成功';
        }
        else
        {
            if ($ranking->messages()['code'] != 0)
            {
                $m3result->code = $ranking->messages()['code'];
                $m3result->messages = $ranking->messages()['messages'];
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