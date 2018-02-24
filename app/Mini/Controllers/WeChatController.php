<?php
namespace App\Mini\Controllers;

use Illuminate\Support\Facades\Log;

/**
 * 微信请求 控制器
 * Class WeChatController
 * @package App\Mini\Controllers
 */
class WeChatController extends Controller
{




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