<?php
namespace App\Http\Middleware;

use App\Tools\M3Result;
use Closure;

/**
 * 检测用户是否绑定手机
 * Class WxAppKeyCheck
 * @package App\Http\Middleware
 */
class UserBindPhoneCheck
{
    /**
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $m3result = new M3Result();
        $session_user = session('User');

        if (!empty($session_user->phone))
        {
            return $next($request);
        }
        else
        {
            $m3result->code = -20;
            $m3result->messages = '请绑定手机号';
            die($m3result->toJson());
        }
    }
}
