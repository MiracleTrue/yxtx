<?php

namespace App\Mini\Controllers;

use App\Entity\MatchRegistration;
use App\Entity\Users;
use App\Models\Registration;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 微信请求 控制器
 * Class WeChatController
 * @package App\Mini\Controllers
 */
class WeChatController extends Controller
{
    /**
     * (报名参赛) 微信支付异步回调
     * @param Request $request
     * @return string
     */
    public function registrationMatchPaymentSuccess(Request $request)
    {
        $app = app('wechat.payment');

        try
        {
            $response = $app->handlePaidNotify(function ($message, $fail) {
                /*初始化*/
                $transaction = new Transaction();

                // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
                $sn_registration = MatchRegistration::where('order_sn', $message['out_trade_no'])->first();

                if ($sn_registration->status != Registration::STATUS_WAIT_PAYMENT)
                { // 如果订单不存在 或者 订单已经支付过了
                    return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
                }

                ///////////// <- 建议在这里调用微信的【订单查询】接口查一下该笔订单的情况，确认是已经支付 /////////////

                if ($message['return_code'] === 'SUCCESS')
                { // return_code 表示通信状态，不代表支付状态

                    $all_registration = MatchRegistration::where('match_id', $sn_registration->match_info->match_id)->where('user_id', $sn_registration->user_id)
                        ->where('type', Registration::TYPE_WECHAT)->get();


                    // 用户是否支付成功
                    if (array_get($message, 'result_code') === 'SUCCESS' &&
                        bcmul($sn_registration->match_info->need_money, $all_registration->count(), 2) == bcdiv($message['total_fee'], 100, 2)
                    )
                    {
                        /*事物*/
                        try
                        {
                            DB::transaction(function () use ($all_registration, $transaction) {

                                $all_registration->each(function () use ($all_registration, $transaction) {
                                    $e_match_registration = MatchRegistration::where('reg_id', $all_registration->reg_id)->where('status', Registration::STATUS_WAIT_PAYMENT)->firstOrFail();
                                    $e_match_registration->status = Registration::STATUS_WAIT_NUMBER;
                                    $e_match_registration->save();

                                    $transaction->accountLogChange($e_match_registration->user_id, $transaction::ACCOUNT_LOG_TYPE_REGISTRATION_FEE, bcsub(0, $e_match_registration->match_info->need_money, 2));

                                    /*收款用户信息改变*/
                                    $e_users = Users::findOrFail($e_match_registration->match_info->user_id);
                                    $last_money = bcsub($e_match_registration->match_info->need_money, bcmul($e_match_registration->match_info->need_money, 0.01, 2), 2);
                                    $e_users->user_money = bcadd($e_users->user_money, $last_money, 2);
                                    $e_users->save();
                                    $transaction->accountLogChange($e_users->user_id, $transaction::ACCOUNT_LOG_TYPE_REGISTRATION_INCOME, $last_money);
                                });

                            });
                        } catch (\Exception $e)
                        {
                            Log::error('订单处理异常');
                            Log::error($e);

                            return $fail('订单处理异常');
                        }
                        return true;// 返回处理完成
                    } // 用户支付失败
                    else
                    {
                        Log::error('订单金额或状态异常');

                        return $fail('订单金额或状态异常');
                    }
                } else
                {
                    Log::error('通信失败，请稍后再通知我');

                    return $fail('通信失败，请稍后再通知我');
                }
            });

            return $response->send(); // return $response;
        } catch (\Exception $e)
        {
            Log::error($e);
            return '请传入xml';
        }
    }


}