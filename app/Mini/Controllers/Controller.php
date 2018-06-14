<?php
namespace App\Mini\Controllers;

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


        /*小程序*/
//        yuxunxiaochengxu@163.com
//        yuan789789
//        AppID:wx4fb1d591de1f9329
//        AppSecret:ea0d8d601e6c09f433b256e15fd18219

        /*阿里key*/
        //Access Key ID      :  LTAInFbXqLFhptN0
        //Access Key Secret  :  G0kBm2WSwpRc7VlKkI9lTR5Uln6kMY

//        DB::enableQueryLog();//开启查询
//
//    dd(DB::getQueryLog());//打印查询SQL
//
    }
}
