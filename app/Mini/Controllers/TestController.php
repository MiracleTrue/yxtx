<?php
namespace App\Mini\Controllers;

use App\Entity\BannerList;
use App\Models\Match;
use App\Models\MyFile;
use App\Models\User;
use App\Tools\M3Result;
use Illuminate\Http\Request;


class TestController extends Controller
{

    public function test()
    {
        $user = new User();
        $session['openid'] = '';

//        $user_info = $user->wxCheckOpenid($session['openid']);

        dd(!$user_info = $user->wxCheckOpenid($session['openid']));

//        dd($user_info);
        if ($user_info = $user->wxCheckOpenid($session['openid']))
        {
            dd(1);
//            /*注册*/
//            if ($user->wxRegister($decryptData))
//            {
//                $m3result->code = -20;
//                $m3result->messages = '请绑定手机号';
//                $m3result->data['open_id'] = $session['openid'];
//                $m3result->data['app_key'] = $user->wxAppkey($session['openid'], $session['session_key']);
//            }
//            else
//            {
//                throw new \Exception($user->messages()['messages']);
//            }
        }
        else
        {
            dd(2);
        }
    }

}