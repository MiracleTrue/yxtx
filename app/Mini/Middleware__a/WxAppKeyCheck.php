<?php
namespace App\Http\Middleware;

use App\Entity\WxAppkey;
use App\Tools\M3Result;
use Closure;

/**
 * 检测微信用户是否登录
 * Class WxAppKeyCheck
 * @package App\Http\Middleware
 */
class WxAppKeyCheck
{
    /**
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
//        $arr = array(
//            'app_key' => 'eyJpdiI6ImRGRWxOZzh6VE56Tmo4MGd2eUgwWWc9PSIsInZhbHVlIjoiWVV1YlwvN3JPc0pFSVBQRUIrR0RrbHZ3WTNISkplZ1NhcWp5UzZNVVNuSVk9IiwibWFjIjoiODczMjE2NjNmOGFmMDQzMDljNGEwZTIzNzk4MWI1NjE3ZDdmOWQwZjVmNmNiZDYwNzUwZWZmOGViYTQ1MzY1ZSJ9',
//        );
//        $request->merge($arr);

        $m3result = new M3Result();
        $app_key = $request->input('app_key');

        if (!empty($app_key))
        {
            $e_wx_appkey = WxAppkey::where('app_key', $app_key)->where('valid_time', '>', now())->first();
            if ($e_wx_appkey != null)
            {
                /*加入session*/
                $user = $e_wx_appkey->user_info;
                $user->app_key = $e_wx_appkey;
                session(['User' => $user]);
                return $next($request);
            }
        }

        $m3result->code = -10;
        $m3result->messages = '请登录';
        die($m3result->toJson());
    }
}
