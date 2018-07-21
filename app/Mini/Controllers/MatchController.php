<?php

namespace App\Mini\Controllers;

use App\Entity\MatchList;
use App\Entity\MatchRegistration;
use App\Exceptions\NetworkBusyException;
use App\Models\Match;
use App\Models\Model;
use App\Models\MyFile;
use App\Models\Registration;
use App\Models\Transaction;
use App\Tools\M3Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * 比赛 控制器
 * Class MatchController
 * @package App\Mini\Controllers
 */
class MatchController extends Controller
{

    /**
     * Api 会员报名确认
     * @param Request $request
     * @return \App\Tools\json
     */
    public function memberConfirm(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
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
            'reg_id' => [
                'required',
                Rule::exists('match_registration', 'reg_id')->where(function ($query) use ($session_user)
                {
                    $query->where('type', Registration::TYPE_MEMBER)->where('status', Registration::STATUS_WAIT_PAYMENT);
                }),
            ]
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $e_reg = MatchRegistration::find($request->input('reg_id'));
            $e_reg->status = Registration::STATUS_WAIT_NUMBER;
            $e_reg->save();
            $m3result->code = 0;
            $m3result->messages = '会员确认无误';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '数据验证失败';
        }

        return $m3result->toJson();
    }

    /**
     * Api 会员报名删除
     * @param Request $request
     * @return \App\Tools\json
     */
    public function memberDelete(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
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
            'reg_id' => [
                'required',
                Rule::exists('match_registration', 'reg_id')->where(function ($query) use ($session_user)
                {
                    $query->where('type', Registration::TYPE_MEMBER);
                }),
            ]
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            MatchRegistration::find($request->input('reg_id'))->delete();
            $m3result->code = 0;
            $m3result->messages = '会员删除成功';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '数据验证失败';
        }

        return $m3result->toJson();
    }

    /**
     * Api 删除未报名比赛
     * @param Request $request
     * @return \App\Tools\json
     * @throws \Throwable
     */
    public function delete(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $match = new Match();
        $session_user = session('User');

        /*验证*/
        $rules = [
            'match_id' => [
                'required',
                Rule::exists('match_list', 'match_id')->where(function ($query) use ($session_user)
                {
                    $query->where('user_id', $session_user->user_id)->where('is_delete', Match::NO_DELETE);
                }),
            ],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            if ($match->getMatchInfo($request->input('match_id'))->registration_sum_number == 0)
            {
                $match->deleteMatch($request->input('match_id'));
                $m3result->code = 0;
                $m3result->messages = '比赛删除成功';
            }
            else
            {
                $m3result->code = 2;
                $m3result->messages = '该比赛已有人报名';
            }
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '数据验证失败';
        }

        return $m3result->toJson();
    }

    /**
     * Api 报名参加比赛
     * @param Request $request
     * @return \App\Tools\json
     * @throws \Throwable
     */
    public function registration(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $match = new Match();
        $session_user = session('User');

        /*验证*/
        $rules = [
            'match_id' => [
                'required',
                Rule::exists('match_list', 'match_id')->where(function ($query) use ($session_user)
                {
                    $query->where('user_id', '!=', $session_user->user_id)->whereIn('status', [Match::STATUS_SIGN_UP, Match::STATUS_GET_NUMBER])->where('match_end_time', '>', now());
                }),
            ],
            'registration' => ['required', function ($key, $val, $fail)
            {

                if (empty($val))
                {
                    $fail('报名JSON 不能为空');
                }

                if (is_array($val))
                {
                    $vl = Validator::make($val, [
                        '*.phone' => [
                            'required',
                            'numeric',
                            'regex:/^((1[3,5,8][0-9])|(14[5,7])|(17[0,6,7,8])|(19[7]))\d{8}$/',
                        ],
                        '*.name' => ['required', 'string'],
                    ]);

                    if ($vl->fails() == true)
                    {
                        $fail('报名JSON 验证失败');
                    }
                }
                else
                {
                    $fail('报名JSON 不能为空');
                }

            }]
        ];
        $request_arr = json_decode($request->getContent(), true);
        $validator = Validator::make($request_arr, $rules);

        if ($validator->passes())
        {
            $registration_arr = $request_arr['registration'];
            $match_info = $match->getMatchInfo($request_arr['match_id']);


            $reg_vli = MatchRegistration::where('user_id', $session_user->user_id)->whereIn('type', [Registration::TYPE_WECHAT])
                ->where('status', '!=', Registration::STATUS_WAIT_PAYMENT)->where('match_id', $request_arr['match_id'])->get()->isNotEmpty();
            if ($reg_vli)
            {
                $m3result->code = 2;
                $m3result->messages = '已经报名,等待抽号';
                return $m3result->toJson();
            }


            /*报名开始*/
            try
            {
                DB::transaction(function () use ($request_arr, $match_info, $registration_arr, $session_user, $request, $m3result)
                {

                    /*删除之前报名*/
                    MatchRegistration::where('user_id', $session_user->user_id)->whereIn('type', [Registration::TYPE_WECHAT])
                        ->where('match_id', $request_arr['match_id'])->delete();

                    /*验证手机号*/
                    $base_phones = MatchRegistration::select('real_phone')->where('match_id', $request_arr['match_id'])
                        ->get()->pluck('real_phone')->toArray();
                    foreach ($registration_arr as $key => $phone)
                    {
                        $self_flag = in_array($phone['phone'], collect(array_except($registration_arr, $key))->pluck('phone')->toArray());
                        $base_flag = in_array($phone['phone'], $base_phones);

                        if ($self_flag || $base_flag)
                        {
                            $m3result->code = 3;
                            $m3result->messages = '手机号已经报名';
                            $m3result->data = $phone['phone'];
                            return $m3result->toJson();
                        }
                    }

                    $e_match_list = MatchList::where('match_id', $match_info->match_id)->whereIn('status', [Match::STATUS_SIGN_UP, Match::STATUS_GET_NUMBER])->where('match_end_time', '>', now())->lockForUpdate()->first();
                    $registration_sum_number = $e_match_list->reg_list()->count();
                    $registration_arr_count = count($registration_arr);

                    if ($e_match_list == null)
                    {
                        throw new NetworkBusyException();
                    }

                    if (bcadd($registration_sum_number, $registration_arr_count) < $e_match_list->match_sum_number)
                    {
                        foreach ($registration_arr as $item)
                        {
                            $e_match_registration = new MatchRegistration();
                            $e_match_registration->user_id = $session_user->user_id;
                            $e_match_registration->match_id = $e_match_list->match_id;
                            $e_match_registration->type = Registration::TYPE_WECHAT;
                            $e_match_registration->status = Registration::STATUS_WAIT_PAYMENT;
                            $e_match_registration->real_name = $item['name'];
                            $e_match_registration->real_phone = $item['phone'];
                            $e_match_registration->match_number = null;
                            $e_match_registration->create_time = now();
                            $e_match_registration->save();
                        }

                        $first_reg = MatchRegistration::where('match_id', $e_match_list->match_id)->where('user_id', $session_user->user_id)
                            ->where('type', Registration::TYPE_WECHAT)->first();

                        $first_reg->order_sn = Model::makeOrderSn();
                        $first_reg->save();

                        $app = app('wechat.payment');

                        $result = $app->order->unify([
                            'body' => $e_match_list->title,
                            'out_trade_no' => $first_reg->order_sn,
                            'total_fee' => bcmul(bcmul($e_match_list->need_money, $registration_arr_count, 2), 100),
                            'notify_url' => url('wxPayment/registrationMatch'), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
                            'trade_type' => 'JSAPI',
                            'openid' => $session_user->openid,
                        ]);

                        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS')
                        {
                            $prepayId = $result['prepay_id'];
                            $config = $app->jssdk->sdkConfig($prepayId); // 返回数组
                            /*消息模板prepay_id*/
                            MatchRegistration::where('match_id', $e_match_list->match_id)->where('user_id', $session_user->user_id)
                                ->where('type', Registration::TYPE_WECHAT)->update(['form_id' => $prepayId]);

                            $m3result->code = 0;
                            $m3result->messages = '比赛报名成功';
                            $m3result->data['wx_pay'] = $config;
                            $m3result->data['match_info'] = $e_match_list;

                        }
                        else
                        {
                            $m3result->code = 5;
                            $m3result->messages = '微信支付失败';
                        }


                    }
                    else
                    {
                        throw new \Exception('该比赛报名人数已满');
                    }
                });
            } catch (\Exception $e)
            {
                $m3result->code = 4;
                $m3result->messages = '报名人数已满';
            }


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
     * Api 会员参加比赛
     * @param Request $request
     * @return \App\Tools\json
     * @throws \Throwable
     */
    public function memberRegistration(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $match = new Match();
        $session_user = session('User');

        /*验证*/
        $rules = [
            'match_id' => [
                'required',
                Rule::exists('match_list', 'match_id')->where(function ($query) use ($session_user)
                {
                    $query->where('user_id', '!=', $session_user->user_id)->whereIn('status', [Match::STATUS_SIGN_UP, Match::STATUS_GET_NUMBER])->where('match_end_time', '>', now());
                }),
            ],
            'real_name' => 'required',
            'form_id' => 'string|nullable'
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $is_registration = MatchRegistration::where('user_id', $session_user->user_id)->whereIn('type', [Registration::TYPE_MEMBER])
                ->where('match_id', $request->input('match_id'))->first();

            /*未报名过*/
            if ($is_registration == null)
            {
                try
                {
                    DB::transaction(function () use ($match, $request, $session_user, $m3result)
                    {
                        $e_match_list = MatchList::where('match_id', $request->input('match_id'))->whereIn('status', [Match::STATUS_SIGN_UP, Match::STATUS_GET_NUMBER])->where('match_end_time', '>', now())->lockForUpdate()->first();
                        $registration_sum_number = $e_match_list->reg_list()->count();

                        if ($e_match_list == null)
                        {
                            throw new \Exception('网络繁忙');
                        }

                        if ($registration_sum_number < $e_match_list->match_sum_number)
                        {
                            $e_match_registration = new MatchRegistration();
                            $e_match_registration->user_id = $session_user->user_id;
                            $e_match_registration->match_id = $e_match_list->match_id;
                            $e_match_registration->type = Registration::TYPE_MEMBER;
                            $e_match_registration->order_sn = null;
                            $e_match_registration->status = Registration::STATUS_WAIT_PAYMENT;
                            $e_match_registration->real_name = $request->input('real_name');
                            $e_match_registration->form_id = $request->has('form_id') ? $request->input('form_id') : null;
                            $e_match_registration->real_phone = $session_user->phone;
                            $e_match_registration->match_number = null;
                            $e_match_registration->create_time = now();
                            $e_match_registration->save();

                            $m3result->code = 0;
                            $m3result->messages = '比赛报名成功';
                            $m3result->data['match_info'] = $match->getMatchInfo($request->input('match_id'));
                        }
                        else
                        {
                            throw new \Exception('报名人数已满');
                        }

                    });
                } catch (\Exception $e)
                {
                    $m3result->code = 2;
                    $m3result->messages = '报名人数已满';
                }
            }
            else
            {
                $m3result->code = 3;
                $m3result->messages = '已经报名,等待抽号';

            }
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '该比赛已撤销或已过报名时间';
            $m3result->data = $validator->errors();
        }
        return $m3result->toJson();
    }


    /**
     * Api 现金参加比赛
     * @param Request $request
     * @return \App\Tools\json
     * @throws \Throwable
     */
    public function cashRegistration(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $match = new Match();
        $registration = new Registration();
        $session_user = session('User');

        /*验证*/
        $rules = [
            'match_id' => [
                'required',
                Rule::exists('match_list', 'match_id')->where(function ($query) use ($session_user)
                {
                    $query->where('user_id', $session_user->user_id)->whereIn('status', [Match::STATUS_SIGN_UP, Match::STATUS_GET_NUMBER])->where('match_end_time', '>', now());
                }),
            ],
            'real_name' => 'required',
            'real_phone' => [
                'required',
                'numeric',
                'regex:/^((1[3,5,8][0-9])|(14[5,7])|(17[0,6,7,8])|(19[7]))\d{8}$/',
            ],
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $match_info = $match->getMatchInfo($request->input('match_id'));

            if ($registration->cashRegistrationMatch($request->input('match_id'), $request->input('real_name'), $request->input('real_phone')))
            {
                $m3result->code = 0;
                $m3result->messages = '现金报名成功';
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
            $m3result->code = 1;
            $m3result->messages = '该比赛已撤销或已过报名时间';
        }
        return $m3result->toJson();
    }

    /**
     * Api 现金报名详情
     * @param Request $request
     * @return \App\Tools\json
     */
    public function cashRegistrationDetail(Request $request)
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
            $list = $registration->getRegistrationList([['match_id', $request->input('match_id')], ['type', $registration::TYPE_CASH]], [['match_registration.create_time', 'desc']], false);
            /*数据过滤*/
            $list->transform(function ($item)
            {
                $item = $item->only('reg_id', 'match_id', 'user_id', 'type', 'type_text', 'status', 'status_text', 'real_name', 'real_phone', 'match_number', 'create_time');
                return $item;
            });
            $m3result->code = 0;
            $m3result->messages = '获取现金报名列表';
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
     * Api 现金报名抽取号码
     * @param Request $request
     * @return \App\Tools\json
     */
    public function cashGetNumber(Request $request)
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
                    $query->where('status', Match::STATUS_GET_NUMBER)->where('user_id', $session_user->user_id);
                }),
            ],
            'reg_id' => [
                'required',
                Rule::exists('match_registration', 'reg_id')->where(function ($query) use ($registration)
                {
                    $query->where('type', $registration::TYPE_CASH)->where('status', $registration::STATUS_WAIT_NUMBER);
                }),
            ]
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            if ($e_reg = $registration->getNumber($request->input('reg_id'), $request->input('match_id')))
            {
                $m3result->code = 0;
                $m3result->messages = '抽取号码成功';
                $m3result->data = $e_reg;
            }
            else
            {
                $m3result->code = 2;
                $m3result->messages = '网络繁忙';
            }
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '未开启抽号';
        }
        return $m3result->toJson();
    }

    /**
     * Api 现金报名一键抽号
     * @param Request $request
     * @return \App\Tools\json
     */
    public function cashAllNumber(Request $request)
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
                    $query->where('status', Match::STATUS_GET_NUMBER)->where('user_id', $session_user->user_id);
                }),
            ]
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $wait_cash_reg_list = $registration->getRegistrationList(
                [['match_id', $request->input('match_id')], ['status', $registration::STATUS_WAIT_NUMBER], ['type', $registration::TYPE_CASH]],
                [['match_registration.create_time', 'asc']],
                false);

            $wait_cash_reg_list->each(function ($item) use ($registration, $request)
            {
                $registration->getNumber($item->reg_id, $request->input('match_id'));
            });

            $m3result->code = 0;
            $m3result->messages = '现金报名一键抽号成功';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '未开启抽号';
        }
        return $m3result->toJson();
    }

    /**
     * Api 已报名抽取号码
     * @param Request $request
     * @return \App\Tools\json
     */
    public function getNumber(Request $request)
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
                    $query->where('status', Match::STATUS_GET_NUMBER);
                }),
            ],
            'reg_id' => [
                'required',
                Rule::exists('match_registration', 'reg_id')->where(function ($query) use ($session_user)
                {
                    $query->where('user_id', $session_user->user_id);
                }),
            ],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $e_match_registration = MatchRegistration::find($request->input('reg_id'));


            if ($e_match_registration->status == Registration::STATUS_WAIT_NUMBER)
            {
                if ($e_reg = $registration->getNumber($e_match_registration->reg_id, $request->input('match_id')))
                {
                    $m3result->code = 0;
                    $m3result->messages = '抽取号码成功';
                    $m3result->data = $e_reg;
                }
                else
                {
                    $m3result->code = 3;
                    $m3result->messages = '网络繁忙';
                }
            }
            elseif ($e_match_registration->status == Registration::STATUS_ALREADY_NUMBER)
            {
                $m3result->code = 0;
                $m3result->messages = '抽取号码成功';
                $m3result->data = $e_match_registration;
            }
            else
            {
                $m3result->code = 2;
                $m3result->messages = '未支付';
            }
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '未到抽号时间';
        }
        return $m3result->toJson();
    }

    /**
     * Api 比赛开启抽号
     * @param Request $request
     * @return \App\Tools\json
     */
    public function openNumber(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $match = new Match();
        $session_user = session('User');

        /*验证*/
        $rules = [
            'match_id' => [
                'required',
                Rule::exists('match_list', 'match_id')->where(function ($query) use ($session_user)
                {
                    $query->where('user_id', $session_user->user_id)->where('status', Match::STATUS_SIGN_UP);
                }),
            ],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes() && $match->matchOpenNumber($request->input('match_id')))
        {
            $m3result->code = 0;
            $m3result->messages = '比赛开启抽号';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '比赛不存在';
        }
        return $m3result->toJson();
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
            $list = $registration->getRegistrationList([['match_id', $request->input('match_id')]], [['match_registration.create_time', 'desc']], false);
            /*数据过滤*/
            $list->transform(function ($item)
            {
                $item = $item->only('reg_id', 'match_id', 'user_id', 'type', 'type_text', 'status', 'status_text', 'real_name', 'real_phone', 'create_time');
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
                $item = $item->only('reg_id', 'match_id', 'user_id', 'type', 'type_text', 'status', 'status_text', 'real_name', 'real_phone', 'create_time', 'match_number');
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
     * Api 比赛抽号列表
     * @param Request $request
     * @return \App\Tools\json
     */
    public function numberList(Request $request)
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
                    //                    $query->whereIn('status', [Match::STATUS_GET_NUMBER, Match::STATUS_END]);
                }),
            ],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $list = $registration->getRegistrationList([['match_id', $request->input('match_id')], ['status', '>', 0]], [['match_registration.create_time', 'desc']], false);
            /*数据过滤*/
            $list->transform(function ($item)
            {
                $item = $item->only('reg_id', 'match_id', 'user_id', 'type', 'type_text', 'status', 'status_text', 'real_name', 'real_phone', 'create_time', 'match_number');
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
            'need_money' => 'required|integer|min:1',
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
            'last_ranking' => 'json',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            if (is_array($last_ranking_arr = json_decode($request->input('last_ranking'), true)))
            {
                $json_rules = [
                    '*.name' => 'required',
                    '*.fish' => 'required',
                    '*.prize' => 'required',
                ];

                $validator_json = Validator::make($last_ranking_arr, $json_rules);
                if ($validator_json->passes() && Validator::make($request->all(), ['last_ranking_time' => 'required|date'])->passes() && $match->releaseMatch($request->all()))
                {
                    $m3result->code = 0;
                    $m3result->messages = '比赛发布成功';
                }
                else
                {
                    $m3result->code = 1;
                    $m3result->messages = '上场排名验证失败';
                    $m3result->data = $validator_json->messages();
                }
            }
            else
            {
                if ($match->releaseMatch($request->all()))
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
            }
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
     * Api 比赛修改
     * @param Request $request
     * @return \App\Tools\json
     * @throws \Throwable
     */
    public function edit(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
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
            'match_content' => 'required',
            'match_photos' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $e_match_list = MatchList::find($request->input('match_id'));

            $e_match_list->match_photos = explode(',', $request->input('match_photos'));
            $e_match_list->match_content = $request->input('match_content');
            $e_match_list->save();

            $m3result->code = 0;
            $m3result->messages = '比赛修改成功';
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
            $m3result->messages = '图片或格式不正确或大小超出限制';
        }

        return $m3result->toJson();
    }

    /**
     * Api 比赛小视频上传
     * @param Request $request
     * @return \App\Tools\json
     */
    public function uploadVideo(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $my_file = new MyFile();

        /*验证*/
        $rules = [
            'video' => 'required|file|max:3072|mimes:mp4',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes())
        {
            $path = $my_file->uploadMatch($request->file('video'));

            $m3result->code = 0;
            $m3result->messages = '比赛小视频上传成功';
            $m3result->data['file_path'] = $path;
            $m3result->data['url_path'] = $my_file->makeUrl($path);
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '视频格式限制(MP4)大小限制(3MB)';
        }

        return $m3result->toJson();
    }

}