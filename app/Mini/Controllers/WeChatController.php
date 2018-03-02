<?php
namespace App\Mini\Controllers;

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
        Log::debug($request->all());

        try
        {
            $response = $app->handlePaidNotify(function ($message, $fail)
            {
                Log::debug($message);
                return $fail('通信测试，请稍后再通知我');


                // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
                $order = 查询订单($message['out_trade_no']);

                if (!$order || $order->paid_at)
                { // 如果订单不存在 或者 订单已经支付过了
                    return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
                }

                ///////////// <- 建议在这里调用微信的【订单查询】接口查一下该笔订单的情况，确认是已经支付 /////////////

                if ($message['return_code'] === 'SUCCESS')
                { // return_code 表示通信状态，不代表支付状态
                    // 用户是否支付成功
                    if (array_get($message, 'result_code') === 'SUCCESS')
                    {
                        $order->paid_at = time(); // 更新支付时间为当前时间
                        $order->status = 'paid';

                        // 用户支付失败
                    }
                    elseif (array_get($message, 'result_code') === 'FAIL')
                    {
                        $order->status = 'paid_fail';
                    }
                }
                else
                {
                    return $fail('通信失败，请稍后再通知我');
                }
                return true; // 返回处理完成
            });

            return $response->send(); // return $response;
        } catch (\Exception $e)
        {
            return '请传入xml';
        }
    }


    //获取小程序二维码
    private function app_code()
    {
        /*初始化*/
        $app = app('wechat.mini_program');
        $response = $app->app_code->get(public_path('uploads'));
        $filename = $response->saveAs(public_path('uploads'), 'appcode.png');
        dd($filename);
    }
}