<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 *
 * Class Controller
 * @package App\Http\Controllers
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        /*时区设置*/
        date_default_timezone_set('PRC');

        /*全局创建时间统一*/
        $GLOBALS['create_time'] = now();

        /*初始化分页大小 10条*/
        if (empty($_COOKIE['PaginationSize']) || is_numeric($_COOKIE['PaginationSize']) == false)
        {
            $_COOKIE['PaginationSize'] = 10;
        }

        /*阿里大鱼*/
        // 军民融合生活保障中心
        // jmrh6666

        /*阿里key*/
        //Access Key ID      :  LTAInFbXqLFhptN0
        //Access Key Secret  :  G0kBm2WSwpRc7VlKkI9lTR5Uln6kMY

//        army  军方    platform  平台    supplier   供货商

//        身份标识: 1.超级管理员  2.平台运营员 3.供货商  4.军方  0.无效


//        DB::enableQueryLog();//开启查询
//
//    dd(DB::getQueryLog());//打印查询SQL

        /*全局config配置,并共享所有视图*/
//        $GLOBALS['shop_config'] = $admin_model->getSystemConfig();
//        View::share('shop_config',$GLOBALS['shop_config']);
//
    }

    public function __destruct()
    {
        /*根据.env文件判断是否需要返回 每个页面的 ViewData*/
        if (env('VIEW_DATA_DEBUG', false) == 'true')
        {
            $route = Route::current();/*当前路由对象*/
            $filter_str = str_replace_first($route->action['namespace'] . '\\', '', $route->action['controller']);
            /*不需要返回ViewData的控制器*/
            $filterable = [
                'IndexController@Index',
            ];
            if (!in_array($filter_str, $filterable) && request()->method() == 'GET')
            {
                dump($route->controller->ViewData);
            }
        }
    }
}
