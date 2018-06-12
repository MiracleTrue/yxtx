<?php
namespace App\Mini\Controllers;

use App\Entity\BannerList;
use App\Models\Match;
use App\Models\MyFile;
use App\Models\Ranking;
use App\Models\Registration;
use App\Models\User;
use App\Tools\M3Result;
use App\Tools\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;


class TestController extends Controller
{

    public function test()
    {

        $r = new Ranking();

        $a = $r->getOneUserPitRanking(5434334);

        dd($a);


//        $a = collect(['user_id'=>'78813','match_id'=>'156489']);
//
//        $b = Crypt::encryptString($a);
//
//        dd($b);
//
//        $a = new Registration();
//        dd($a->makeOrderSn());
//
//        dd(MyHelper::money_format(20.01));

        $m = new Match();

//        dd($m->matchDetailOptionButton());


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