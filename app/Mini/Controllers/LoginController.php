<?php

namespace App\Mini\Controllers;

use App\Models\User;
use App\Tools\M3Result;
use Illuminate\Http\Request;

/**
 * 登录 控制器
 * Class LoginController
 * @package App\Mini\Controllers
 */
class LoginController extends Controller
{


    public function login(Request $request)
    {
        /*初始化*/
        $app = app('wechat.mini_program');
        $user = new User();
        $m3result = new M3Result();

        try
        {
            $session = $app->auth->session($request->input('jsCode'));

            if ($e_wx_openid = $user->wxCheckOpenid($session['open_id']))
            {
                $e_users = $e_wx_openid->user_info;
                if ($e_users->is_disable == $user::IS_DISABLE)
                {
                    $m3result->code = 3;
                    $m3result->messages = '用户被禁用';
                }
                else
                {
                    $m3result->code = 0;
                    $m3result->messages = '登录成功';
                    $m3result->data['open_id'] = $session['open_id'];
                    $m3result->data['app_key'] = $user->wxAppkey($session['open_id'], $session['session_key']);
                }
            }
            else
            {
                $m3result->code = 2;
                $m3result->messages = '用户未注册';
            }
        } catch (\Exception $e)
        {
            dd($e);
            $m3result->code = 1;
            $m3result->messages = '登录失败';
        }

        return $m3result->toJson();
    }

    public function register(Request $request)
    {
        /*初始化*/
        $app = app('wechat.mini_program');
        $user = new User();
        $m3result = new M3Result();

        $session = $app->auth->session($request->input('jsCode'));
        $data = $app->encryptor->decryptData($session['session_key'],$request->input('iv'),$request->input('encryptedData'));
        dd($data);
    }
}