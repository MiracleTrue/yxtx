<?php
namespace App\Mini\Controllers;

use App\Entity\MatchRegistration;
use App\Models\Registration;
use App\Models\Transaction;
use Illuminate\Http\Request;
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
            $response = $app->handlePaidNotify(function ($message, $fail)
            {
                info($message);
                /*初始化*/
                $transaction = new Transaction();

                // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
                $e_match_registration = MatchRegistration::where('order_sn', $message['out_trade_no'])->first();

                if ($e_match_registration->status != Registration::STATUS_WAIT_PAYMENT)
                { // 如果订单不存在 或者 订单已经支付过了
                    return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
                }

                ///////////// <- 建议在这里调用微信的【订单查询】接口查一下该笔订单的情况，确认是已经支付 /////////////

                if ($message['return_code'] === 'SUCCESS')
                { // return_code 表示通信状态，不代表支付状态
                    // 用户是否支付成功
                    if (array_get($message, 'result_code') === 'SUCCESS' && $e_match_registration->match_info->need_money == bcdiv($message['total_fee'], 100, 2) && $transaction->registrationMatchPaySuccess($e_match_registration->reg_id))
                    {
                        return true; // 返回处理完成
                    }
                    // 用户支付失败
                    else
                    {
                        return $fail('订单金额或状态异常');
                    }
                }
                else
                {
                    return $fail('通信失败，请稍后再通知我');
                }
            });

            return $response->send(); // return $response;
        } catch (\Exception $e)
        {
            return '请传入xml';
        }
    }


}