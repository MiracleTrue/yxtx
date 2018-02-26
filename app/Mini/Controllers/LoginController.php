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

            if ($e_wx_openid = $user->wxCheckOpenid($session['openid']))
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
                    $m3result->data['open_id'] = $session['openid'];
                    $m3result->data['app_key'] = $user->wxAppkey($session['openid'], $session['session_key']);
                }
            }
            else
            {
                $m3result->code = 2;
                $m3result->messages = '用户未注册';
            }
        } catch (\Exception $e)
        {
            $m3result->code = 1;
            $m3result->messages = '数据验证失败';
        }

        return $m3result->toJson();
    }

    public function register(Request $request)
    {
        /*初始化*/
        $app = app('wechat.mini_program');
        $user = new User();
        $m3result = new M3Result();

        try
        {
            $session = $app->auth->session($request->input('jsCode'));
            $decryptData = $app->encryptor->decryptData($session['session_key'], $request->input('iv'), $request->input('encryptedData'));

            if ($user->wxCheckOpenid($session['openid']))
            {
                if ($user->wxRegister($decryptData))
                {
                    $m3result->code = 0;
                    $m3result->messages = '注册并登录成功';
//                    $m3result->data['open_id'] = $session['openid'];
//                    $m3result->data['app_key'] = $user->wxAppkey($session['openid'], $session['session_key']);
                }
                else
                {
                    throw new \Exception($user->messages()['messages']);
                }
            }
            else
            {
                $m3result->code = 2;
                $m3result->messages = '用户已注册';
            }
        } catch (\Exception $e)
        {
            dd($e);
            $m3result->code = 1;
            $m3result->messages = '数据验证失败';
        }

        return $m3result->toJson();
    }
}