<?php

namespace App\Mini\Controllers;

use App\Entity\GoldGoods;
use App\Entity\MatchRegistration;
use App\Entity\SilverGoods;
use App\Entity\Users;
use App\Models\Match;
use App\Models\MyFile;
use App\Models\Ranking;
use App\Models\Registration;
use App\Models\Sms;
use App\Models\Transaction;
use App\Models\User;
use App\Tools\M3Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 用户 控制器
 * Class UserController
 * @package App\Mini\Controllers
 */
class UserController extends Controller
{
    /**
     * Api 用户提现(银联)
     * @param Request $request
     * @return \App\Tools\json
     */
    public function withdrawUnionPay(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $transaction = new Transaction();
        $session_user = session('User');

        /*验证*/
        $rules = [
            'money' => 'required|numeric|between:0.01,' . Users::find($session_user->user_id)->user_money,
            'account' => 'required',
            'name' => 'required',
            'bank' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes() && $transaction->userWithdrawUnionPay(
                $session_user->user_id,
                $request->input('money'),
                $request->input('account'),
                $request->input('name'),
                $request->input('bank'))
        )
        {
            $m3result->code = 0;
            $m3result->messages = '申请提现成功';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '余额不足';
        }
        return $m3result->toJson();
    }

    /**
     * Api 用户提现(微信零钱)
     * @param Request $request
     * @return \App\Tools\json
     */
    public function withdrawWeChat(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $transaction = new Transaction();
        $session_user = session('User');

        /*验证*/
        $rules = [
            'money' => 'required|numeric|between:0.01,' . Users::find($session_user->user_id)->user_money,
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes() && $transaction->userWithdrawWeChat(
                $session_user->user_id,
                $request->input('money'))
        )
        {
            $m3result->code = 0;
            $m3result->messages = '申请提现成功';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '余额不足';
        }
        return $m3result->toJson();
    }

    /**
     * Api 用户账户流水
     * @param Request $request
     * @return \App\Tools\json
     */
    public function accountHistory(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $session_user = session('User');
        $transaction = new Transaction();

        $list = $transaction->getAccountLog([['user_id', $session_user->user_id]]);
        $m3result->code = 0;
        $m3result->messages = '账户流水列表获取成功';
        $m3result->data = $list;

        return $m3result->toJson();
    }

    /**
     * Api 用户金币账户流水
     * @param Request $request
     * @return \App\Tools\json
     */
    public function goldHistory(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $session_user = session('User');
        $transaction = new Transaction();

        $list = $transaction->getGoldLog([['user_id', $session_user->user_id]]);
        $m3result->code = 0;
        $m3result->messages = '金币账户流水列表获取成功';
        $m3result->data = $list;

        return $m3result->toJson();
    }

    /**
     * Api 用户银币账户流水
     * @param Request $request
     * @return \App\Tools\json
     */
    public function silverHistory(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $session_user = session('User');
        $transaction = new Transaction();

        $list = $transaction->getSilverLog([['user_id', $session_user->user_id]]);
        $m3result->code = 0;
        $m3result->messages = '银币账户流水列表获取成功';
        $m3result->data = $list;

        return $m3result->toJson();
    }

    /**
     * Api 我报名的比赛
     * @param Request $request
     * @return \App\Tools\json
     */
    public function myRegistration(Request $request)
    {
        /*初始化*/
        $registration = new Registration();
        $my_file = new MyFile();
        $session_user = session('User');
        $m3result = new M3Result();

        $list = $registration->getRegistrationList([['user_id', $session_user->user_id]]);
        /*数据过滤*/
        $list->transform(function ($item) use ($my_file)
        {
            $arr = $item->match_info->only('match_id', 'title', 'status', 'status_text', 'address_name', 'match_start_time', 'match_end_time', 'match_sum_number', 'fish_number', 'need_money', 'first_photo');
            unset($item->match_info);
            $item->match_info = $arr;
            return $item;
        });
        $m3result->code = 0;
        $m3result->messages = '报名比赛列表获取成功';
        $m3result->data = $list;

        return $m3result->toJson();
    }

    /**
     * Api 我发布的比赛
     * @param Request $request
     * @return \App\Tools\json
     */
    public function myMatch(Request $request)
    {
        /*初始化*/
        $match = new Match();
        $my_file = new MyFile();
        $session_user = session('User');
        $m3result = new M3Result();

        $list = $match->getMatchList([['user_id', $session_user->user_id]]);
        /*数据过滤*/
        $list->transform(function ($item) use ($my_file)
        {
            $item->first_photo = $item->match_photos[0] != null ? $my_file->makeUrl($item->match_photos[0]) : null;
            $item = $item->only('match_id', 'title', 'status', 'status_text', 'address_name', 'match_start_time', 'match_end_time', 'match_sum_number', 'fish_number', 'need_money', 'first_photo');
            return $item;
        });

        $m3result->code = 0;
        $m3result->messages = '发布比赛列表获取成功';
        $m3result->data = $list;

        return $m3result->toJson();
    }

    /**
     * Api 获取当前用户详情
     * @param Request $request
     * @return \App\Tools\json
     */
    public function info(Request $request)
    {
        /*初始化*/
        $session_user = session('User');
        $m3result = new M3Result();
        $user = new User();
        $ranking = new Ranking();

        if ($session_user != null)
        {
            $e_users = $user->getUserInfo($session_user->user_id);
            $e_users->release_count = $e_users->match_list()->count();
            $e_users->registration_count = $e_users->registration_list()->count();
            $e_users->gold_goods_count = GoldGoods::count();
            $e_users->silver_goods_count = SilverGoods::count();
            $e_users->pit_ranking = $ranking->getOneUserPitRanking($session_user->user_id);
            $e_users->match_ranking = $ranking->getOneUserMatchRanking($session_user->user_id);
            $m3result->code = 0;
            $m3result->messages = '获取用户详情成功';
            $m3result->data = $e_users;
        }
        else
        {
            $m3result->code = -10;
            $m3result->messages = '请登录';
        }
        return $m3result->toJson();
    }

    /**
     * Api 用户更改服务城市
     * @param Request $request
     * @return \App\Tools\json
     */
    public function locationSet(Request $request)
    {
        /*初始化*/
        $user = new User();
        $m3result = new M3Result();
        $session_user = session('User');

        /*验证*/
        $rules = [
            'city' => 'required|exists:match_address,city',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes() && $user->locationSet($session_user->user_id, $request->input('city')))
        {
            $m3result->code = 0;
            $m3result->messages = '更改服务城市成功';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '该城市未开通服务';
        }
        return $m3result->toJson();
    }

    /**
     * Api 登录请求
     * @param Request $request
     * @return \App\Tools\json
     */
    public function login(Request $request)
    {
        /*初始化*/
        $app = app('wechat.mini_program');
        $user = new User();
        $m3result = new M3Result();

        try
        {
            /*验证*/
            $rules = [
                'jsCode' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails())
            {
                throw new \Exception('数据验证失败');
            }
            $session = $app->auth->session($request->input('jsCode'));

            if (!$user_info = $user->wxCheckOpenid($session['openid']))
            {
                $m3result->code = 3;
                $m3result->messages = '用户未注册';
            }
            else
            {
                if ($user_info->is_disable == $user::IS_DISABLE)
                {
                    $m3result->code = 2;
                    $m3result->messages = '该用户被禁用';
                }
                elseif (empty($user_info->phone))
                {
                    /*需绑定手机*/
                    $m3result->code = -20;
                    $m3result->messages = '请绑定手机号';
                    $m3result->data['open_id'] = $session['openid'];
                    $m3result->data['app_key'] = $user->wxAppkey($session['openid'], $session['session_key']);
                }
                else
                {
                    /*登录*/
                    $m3result->code = 0;
                    $m3result->messages = '登录成功';
                    $m3result->data['open_id'] = $session['openid'];
                    $m3result->data['app_key'] = $user->wxAppkey($session['openid'], $session['session_key']);
                }
            }
        } catch (\Exception $e)
        {
            $m3result->code = 1;
            $m3result->messages = 'Code已使用,请重新生成';
        }

        return $m3result->toJson();
    }

    /**
     * Api 注册请求
     * @param Request $request
     * @return \App\Tools\json
     * @throws \Throwable
     */
    public function register(Request $request)
    {
        /*初始化*/
        $app = app('wechat.mini_program');
        $user = new User();
        $m3result = new M3Result();

        try
        {
            /*验证*/
            $rules = [
                'jsCode' => 'required',
                'iv' => 'required',
                'encryptedData' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails())
            {
                throw new \Exception('数据验证失败', 1);
            }
            $session = $app->auth->session($request->input('jsCode'));

            $decryptData = $app->encryptor->decryptData($session['session_key'], $request->input('iv'), $request->input('encryptedData'));

            if (!$user_info = $user->wxCheckOpenid($session['openid']))
            {
                /*注册*/
                if ($user->wxRegister($decryptData))
                {
                    $m3result->code = 0;
                    $m3result->messages = '注册并登录成功';
                    $m3result->data['open_id'] = $session['openid'];
                    $m3result->data['app_key'] = $user->wxAppkey($session['openid'], $session['session_key']);
                }
                else
                {
                    throw new \Exception($user->messages()['messages']);
                }
            }
            else
            {
                $m3result->code = 2;
                $m3result->messages = '用户已注册,请登录';
            }
        } catch (\Exception $e)
        {
            if ($e->getCode() != 1)
            {
                Log::emergency($e);
            }
            $m3result->code = 1;
            $m3result->messages = 'Code已使用,参数缺少';
        }

        return $m3result->toJson();
    }

    /**
     * Api 更新当前用户信息
     * @param Request $request
     * @return \App\Tools\json
     */
    public function update(Request $request)
    {
        /*初始化*/
        $app = app('wechat.mini_program');
        $m3result = new M3Result();
        $session_user = session('User');

        try
        {
            /*验证*/
            $rules = [
                'jsCode' => 'required',
                'iv' => 'required',
                'encryptedData' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails())
            {
                throw new \Exception('数据验证失败', 1);
            }
            $session = $app->auth->session($request->input('jsCode'));
            $decryptData = $app->encryptor->decryptData($session['session_key'], $request->input('iv'), $request->input('encryptedData'));

            $e_users = Users::find($session_user->user_id);
            $e_users->nick_name = $decryptData['nickName'];
            $e_users->avatar = $decryptData['avatarUrl'];
            $e_users->save();

            $m3result->code = 0;
            $m3result->messages = '用户更新成功';

        } catch (\Exception $e)
        {
            if ($e->getCode() != 1)
            {
                Log::emergency($e);
            }
            $m3result->code = 1;
            $m3result->messages = 'Code已使用,参数缺少';
        }

        return $m3result->toJson();
    }

    /**
     * Api 绑定手机 请求处理
     * @param Request $request
     * @return \App\Tools\json
     */
    public function bindPhone(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $session_user = session('User');
        $user = new User();

        $rules = [
            'phone' => [
                'required',
                'numeric',
                'regex:/^((1[3,5,8][0-9])|(14[5,7])|(17[0,6,7,8])|(19[7]))\d{8}$/',
            ],
            'code' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            if ($user->checkSmsCode($request->input('phone'), $request->input('code')))
            {
                $user->bindPhone($session_user->user_id, $request->input('phone'));
                $m3result->code = 0;
                $m3result->messages = '绑定成功';
            }
            else
            {
                $m3result->code = 1;
                $m3result->messages = '验证码不正确';
            }
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '验证码不正确';
        }
        return $m3result->toJson();
    }

    /**
     * Api 获取手机验证码
     * @param Request $request
     * @return \App\Tools\json
     */
    public function smsCode(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $sms = new Sms();
        $user = new User();

        /*验证*/
        $rules = [
            'phone' => [
                'required',
                'numeric',
                'regex:/^((1[3,5,8][0-9])|(14[5,7])|(17[0,6,7,8])|(19[7]))\d{8}$/',
            ]
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $sms->sendSms(Sms::SMS_SIGNATURE_1, Sms::USER_BIND_PHONE_CODE, $request->input('phone'), ['code' => $user->makeSmsCode($request->input('phone'))]);
            $m3result->code = 0;
            $m3result->messages = '短信验证码已发送至手机';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '手机号码不合法';
        }

        return $m3result->toJson();

    }
}