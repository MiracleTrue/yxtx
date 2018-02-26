<?php
namespace App\Mini\Controllers;

use App\Models\Match;
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
        }
    }

}