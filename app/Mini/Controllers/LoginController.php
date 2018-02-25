<?php
namespace App\Mini\Controllers;
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

        $session = $app->auth->session($request->input('jsCode'));

        dd($session);

        dd($request->all(),$app);

    }
}