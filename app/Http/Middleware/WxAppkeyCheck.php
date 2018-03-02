<?php

namespace App\Http\Middleware;

use App\Entity\WxAppkey;
use App\Tools\M3Result;
use Closure;
use Illuminate\Support\Facades\Route;

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
//        $arr = [
//          'app_key'=>'eyJpdiI6IitYZ1dRRkdsUnluTWVrWkZwZE5saFE9PSIsInZhbHVlIjoiVGF0dFRJVk8ybWIyWW91bGJNcG1obW1YbDdzUkU4MXViS2sxdXduRmFDUT0iLCJtYWMiOiJkODEwZThkNzBmMzczZWEwNThiYWU4MTVhOWVmNTdmZGIyYjAwY2U0NDNjZDBiY2IzZTBiYTk4YzlmMGExMTNhIn0=',
//        ];
//        $request->merge($arr);

        $route = Route::current();/*当前路由对象*/

        /*不需要登录的请求*/
        $filterable = array(
            url('login'),/*登录*/
            url('index/match'),/*首页比赛列表*/
            url('index/banner'),/*首页banner图*/
            url('location/serviceCity'),/*获取服务开通城市*/
            url('index/search'),/*搜索比赛*/
            url('match/info'),/*获取比赛详情*/
        );

        $m3result = new M3Result();
        $app_key = $request->input('app_key');

        if (in_array(url($route->uri), $filterable))
        {
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
            return $next($request);
        }
        else if (!empty($app_key))
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
