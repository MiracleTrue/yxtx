<?php
namespace App\Mini\Controllers;

use App\Entity\MatchList;
use App\Entity\MatchRegistration;
use App\Models\Match;
use App\Models\MyFile;
use App\Models\Registration;
use App\Models\Transaction;
use App\Tools\M3Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * 比赛 控制器
 * Class MatchController
 * @package App\Mini\Controllers
 */
class MatchController extends Controller
{

    //报名参加比赛
    public function registration(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $match = new Match();
        $registration = new Registration();
        $session_user = session('User');
        $transaction = new Transaction();

        /*验证*/
        $rules = [
            'match_id' => [
                'required',
                Rule::exists('match_list', 'match_id')->where(function ($query) use ($session_user)
                {
                    $query->where('user_id', '!=', $session_user->user_id)->where('status', Match::STATUS_SIGN_UP)->where('match_start_time', '>=', now());
                }),
            ],
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $is_registration = MatchRegistration::where('user_id', $session_user->user_id)->where('match_id', $request->input('match_id'))->first();
            $match_info = $match->getMatchInfo($request->input('match_id'));

            /*未报名过*/
            if ($is_registration == null)
            {
                if ($e_reg = $registration->registrationMatch($session_user->user_id, $request->input('match_id')))
                {
                    $m3result->code = 0;
                    $m3result->messages = '比赛报名成功';
//                    $m3result->data['order_sn'] = $e_reg->order_sn;
//                    $m3result->data['notify_url'] = Transaction::getRegistrationMatchNotifyUrl();
                    $m3result->data['match_info'] = $match_info;
                }
                else
                {
                    $m3result->code = 2;
                    $m3result->messages = '报名人数已满';
                }
            }
            else
            {
                if ($is_registration->status == Registration::STATUS_WAIT_PAYMENT)
                {
                    $m3result->code = 0;
                    $m3result->messages = '比赛报名成功';


                    $transaction->RegistrationMatchPaymentStart($is_registration->reg_id);

//                    $m3result->data['order_sn'] = $is_registration->order_sn;
//                    $m3result->data['notify_url'] = Transaction::getRegistrationMatchNotifyUrl();
                    $m3result->data['match_info'] = $match_info;
                }
                else
                {
                    $m3result->code = 3;
                    $m3result->messages = '已经报名,等待抽号';
                }
            }
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '该比赛已撤销或已过报名时间';
        }
        return $m3result->toJson();
    }

    //开启抽号
    public function openNumber()
    {
        
    }

    /**
     * Api 比赛报名详情
     * @param Request $request
     * @return \App\Tools\json
     */
    public function registrationDetail(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $registration = new Registration();
        $session_user = session('User');

        /*验证*/
        $rules = [
            'match_id' => [
                'required',
                Rule::exists('match_list', 'match_id')->where(function ($query) use ($session_user)
                {
                    $query->where('user_id', $session_user->user_id);
                }),
            ],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $list = $registration->getRegistrationList([['match_id', $request->input('match_id')], ['status', '!=', $registration::STATUS_WAIT_PAYMENT]], [['match_registration.create_time', 'desc']], false);
            /*数据过滤*/
            $list->transform(function ($item)
            {
                $item->user_info = $item->user_info->only('nick_name', 'phone');
                $item = $item->only('reg_id', 'status', 'status_text', 'create_time', 'user_info');
                return $item;
            });
            $m3result->code = 0;
            $m3result->messages = '获取比赛报名列表';
            $m3result->data = $list;
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '比赛不存在';
        }
        return $m3result->toJson();
    }

    /**
     * Api 比赛抽号详情
     * @param Request $request
     * @return \App\Tools\json
     */
    public function numberDetail(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $registration = new Registration();
        $session_user = session('User');

        /*验证*/
        $rules = [
            'match_id' => [
                'required',
                Rule::exists('match_list', 'match_id')->where(function ($query) use ($session_user)
                {
                    $query->where('user_id', $session_user->user_id)->whereIn('status', [Match::STATUS_GET_NUMBER, Match::STATUS_END]);
                }),
            ],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $list = $registration->getRegistrationList([['match_id', $request->input('match_id')], ['status', $registration::STATUS_ALREADY_NUMBER]], [['match_registration.create_time', 'desc']], false);
            /*数据过滤*/
            $list->transform(function ($item)
            {
                $item->user_info = $item->user_info->only('nick_name', 'phone');
                $item = $item->only('reg_id', 'status', 'status_text', 'create_time', 'match_number', 'user_info');
                return $item;
            });
            $m3result->code = 0;
            $m3result->messages = '获取比赛抽号列表';
            $m3result->data = $list;
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '比赛不存在';
        }
        return $m3result->toJson();
    }

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
            $info['option_button'] = $match->matchDetailOptionButton($info);
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
     * @throws \Throwable
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
            'match_start_time' => 'required|date|after:now',
            'match_end_time' => 'required|date|after:now',
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