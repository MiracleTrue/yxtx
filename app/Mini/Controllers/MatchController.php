<?php
namespace App\Mini\Controllers;

use App\Models\Match;
use App\Models\MyFile;
use App\Tools\M3Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * 比赛 控制器
 * Class MatchController
 * @package App\Mini\Controllers
 */
class MatchController extends Controller
{

    /**
     * Api 获取比赛详情
     * @param Request $request
     * @return \App\Tools\json
     */
    public function info(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $match = new Match();

        /*验证*/
        $rules = [
            'match_id' => 'required|exists:match_list,match_id',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $info = $match->getMatchInfo($request->input('match_id'));
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
     * Api 比赛发布
     * @param Request $request
     * @return \App\Tools\json
     */
    public function release(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $match = new Match();

        /*验证*/
        $rules = [
            'title' => 'required',
            'need_money' => 'required|numeric',
            'hotline' => 'required',
            'address_name' => 'required',
            'address_coordinate_lat' => 'required|numeric',
            'address_coordinate_lng' => 'required|numeric',
            'match_start_time' => 'required|date',
            'match_end_time' => 'required|date',
            'match_start_number' => 'required|integer|min:0',
            'match_end_number' => 'required|integer|min:' . bcadd($request->input('match_start_number'), 1),
            'match_content' => 'required',
            'match_service' => 'required',
            'fish_number' => 'required',
            'match_photos' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes() && $match->releaseMatch($request->all()))
        {
            $m3result->code = 0;
            $m3result->messages = '比赛发布成功';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '数据验证失败';
            $m3result->data = $validator->messages();
        }

        return $m3result->toJson();
    }

    /**
     * Api 比赛图片上传
     * @param Request $request
     * @return \App\Tools\json
     */
    public function uploadPhoto(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $my_file = new MyFile();

        /*验证*/
        $rules = [
            'image' => 'required|image|mimes:jpeg,gif,png',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $path = $my_file->uploadMatch($request->file('image'));

            $m3result->code = 0;
            $m3result->messages = '比赛图片上传成功';
            $m3result->data['file_path'] = $path;
            $m3result->data['url_path'] = $my_file->makeUrl($path);
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '图片格式不正确或大小超出限制';
        }

        return $m3result->toJson();
    }

}