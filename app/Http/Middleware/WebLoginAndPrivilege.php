<?php

namespace App\Http\Middleware;

use App\Models\CommonModel;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Route;

/**
 * 网页: 用户登录session验证与权限检测的中间件
 * Class LoginAndPrivilege
 * @package App\Http\Middleware
 */
class WebLoginAndPrivilege
{
    /**
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = Route::current();/*当前路由对象*/

        /*中间件白名单数组(不需要登录验证的白名单)*/
        $filterable = array(
//            action('Admin\IndexController@Login'),/*登录页面*/
//            action('Admin\IndexController@NoPrivilege'),/*没有权限页面*/
//            url('admin/language/{lang}'),/*当前语言更改*/
        );
        /*超级管理员权限拒绝数组*/
        $administrator_deny = array(
            /*平台*/
//            action('PlatformController@NeedRelease'),/*平台发布需求*/
            action('PlatformController@OfferAllocation'),/*首次分配供应商*/
            action('PlatformController@OfferReAllocation'),/*二次分配供应商*/
            action('PlatformController@OrderConfirm'),/*确认订单*/
            action('PlatformController@ConfirmReceive'),/*确认收货*/
            action('PlatformController@InventorySupply'),/*库存供应*/
            action('PlatformController@SendArmy'),/*发货到军方*/

            /*军方*/
            action('ArmyController@NeedRelease'),/*军方发布需求*/
            action('ArmyController@NeedEdit'),/*军方修改需求*/
            action('ArmyController@NeedDelete'),/*军方删除需求*/
            action('ArmyController@ConfirmReceive'),/*军方确认收货*/

            /*供应商*/
            action('SupplierController@OfferSubmit'),/*同意供货*/
            action('SupplierController@OfferDeny'),/*拒绝供货*/
            action('SupplierController@SendProduct'),/*配货*/
        );

        if (in_array(url($route->uri), $filterable) || empty($route->action['identity']))
        {
            return $next($request);
        }

        /*判断是否登录  已登录状态ManageUser === true*/
        if ($request->session()->exists('ManageUser') === true)
        {
            /*权限检测*/
            if ($request->session()->get('ManageUser')->identity == User::ADMINISTRATOR)/*超级管理员*/
            {
                if (in_array(url($route->uri), $administrator_deny))/*超级管理员权限拒绝数组*/
                {
                    return CommonModel::noPrivilegePrompt($request);/*根据请求方式,返回不同的"没有"权限的信息*/
                }
                return $next($request);
            }
            elseif ($request->session()->get('ManageUser')->identity == User::PLATFORM_ADMIN && in_array(User::PLATFORM_ADMIN, $route->action['identity']))/*平台用户*/
            {
                return $next($request);
            }
            elseif ($request->session()->get('ManageUser')->identity == User::SUPPLIER_ADMIN && in_array(User::SUPPLIER_ADMIN, $route->action['identity']))/*供货商用户*/
            {
                return $next($request);
            }
            elseif ($request->session()->get('ManageUser')->identity == User::ARMY_ADMIN && in_array(User::ARMY_ADMIN, $route->action['identity']))/*军方用户*/
            {
                return $next($request);
            }
            else
            {
                return CommonModel::noPrivilegePrompt($request);/*根据请求方式,返回不同的"没有"权限的信息*/
            }
        }
        else
        {
            /*未登录跳转登录页面*/
            return redirect(action('IndexController@Login'));
        }
    }

}
