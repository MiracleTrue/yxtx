<?php
/**
 * Created by BocWeb.
 * Author: Walker  QQ:120007700
 * Date  : 2017/10/12
 * Time  : 11:17
 */
namespace App\Http\Controllers;

use App\Entity\OrderOffer;
use App\Entity\Orders;
use App\Models\Army;
use App\Models\Platform;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Tools\M3Result;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Request;

/**
 * 首页控制器
 * Class IndexController
 * @package App\Http\Controllers\Admin
 */
class IndexController extends Controller
{
    public $ViewData = array(); /*传递页面的数组*/

    /**
     * 后台主框架
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function Index()
    {
        /*初始化*/
        $manage_u = session('ManageUser');
        $this->ViewData['order_status'] = array();
        $this->ViewData['iframe_url'] = action('IndexController@Welcome');
        $this->ViewData['manage_user'] = $manage_u;

        /*订单导航*/
        switch ($manage_u->identity)
        {
            case User::ARMY_ADMIN :/*军方*/
                $this->ViewData['order_status']['待确认'] = Orders::where('army_id', $manage_u->user_id)->whereIn('status', [Army::ORDER_AWAIT_ALLOCATION])->count();
                $this->ViewData['order_status']['已确认'] = Orders::where('army_id', $manage_u->user_id)->whereNotIn('status', [Army::ORDER_AWAIT_ALLOCATION, Army::ORDER_SEND_ARMY, Army::ORDER_SUCCESSFUL])->count();
                $this->ViewData['order_status']['已发货'] = Orders::where('army_id', $manage_u->user_id)->whereIn('status', [Army::ORDER_SEND_ARMY])->count();
                $this->ViewData['order_status']['已到货'] = Orders::where('army_id', $manage_u->user_id)->whereIn('status', [Army::ORDER_SUCCESSFUL])->count();
                break;
            case User::SUPPLIER_ADMIN :/*供应商*/
                $this->ViewData['order_status']['待回复'] = OrderOffer::where('user_id', $manage_u->user_id)->where('status', Supplier::OFFER_AWAIT_REPLY)->count();
                $this->ViewData['order_status']['待确认'] = OrderOffer::where('user_id', $manage_u->user_id)->where('status', Supplier::OFFER_AWAIT_CONFIRM)->count();
                $this->ViewData['order_status']['待发货'] = OrderOffer::where('user_id', $manage_u->user_id)->where('status', Supplier::OFFER_AWAIT_SEND)->count();
                $this->ViewData['order_status']['已发货'] = OrderOffer::where('user_id', $manage_u->user_id)->where('status', Supplier::OFFER_ALREADY_SEND)->count();
                $this->ViewData['order_status']['已收货'] = OrderOffer::where('user_id', $manage_u->user_id)->where('status', Supplier::OFFER_ALREADY_RECEIVE)->count();
                $this->ViewData['order_status']['已拒绝'] = OrderOffer::where('user_id', $manage_u->user_id)->where('status', Supplier::OFFER_ALREADY_DENY)->count();
                $this->ViewData['order_status']['已过期'] = OrderOffer::where('user_id', $manage_u->user_id)->where('status', Supplier::OFFER_OVERDUE)->count();
                break;
            default :/*平台和超级管理员*/
                $this->ViewData['order_status']['待分配'] = Orders::whereIn('status', [Platform::ORDER_AWAIT_ALLOCATION, Platform::ORDER_AGAIN_ALLOCATION])->count();
                $this->ViewData['order_status']['已分配'] = Orders::whereIn('status', [Platform::ORDER_ALREADY_ALLOCATION, Platform::ORDER_ALREADY_CONFIRM, Platform::ORDER_ALREADY_RECEIVE, Platform::ORDER_ALLOCATION_PLATFORM])->count();
                $this->ViewData['order_status']['已发货'] = Orders::whereIn('status', [Platform::ORDER_SEND_ARMY])->count();
                $this->ViewData['order_status']['交易成功'] = Orders::whereIn('status', [Platform::ORDER_SUCCESSFUL])->count();
                break;
        }

        /*iframe_url*/
        switch($manage_u->identity)
        {
            case User::SUPPLIER_ADMIN :
                $this->ViewData['iframe_url'] = action('SupplierController@NeedList');
                break;
        }

        return view('index', $this->ViewData);
    }

    /**
     * 后台首页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function Welcome()
    {
        /*初始化*/
        $product = new Product();
        $this->ViewData['product_list'] = $product->getWelcomeProductList();

        return view('welcome', $this->ViewData);
    }

    /**
     * 登录页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function Login()
    {
        return view('login', $this->ViewData);
    }

    /**
     * 用户登出(退出)处理,跳转注册页面
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function Logout()
    {
        /*删除管理员的session*/
        session()->forget('ManageUser');
        return redirect(action('IndexController@Login'));
    }

    /**
     * Ajax 用户登录 提交处理
     * @param Request $request
     * @return \App\Tools\json
     */
    public function LoginSubmit(Request $request)
    {
        /*初始化*/
        $user = new User();
        $m3result = new M3Result();
        $manage_u = null;

        $rules = [
            'user_name' => [
                'required',
                'between:4,16',
                Rule::exists('users')->where(function ($query)
                {
                    $query->where('user_name', $GLOBALS['request']->input('user_name'));
                }),
            ],
            'password' => 'required|min:6',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->passes() && $manage_u = $user->userLoginFromName($request->input('user_name'), $request->input('password')))
        {   /*验证通过并且用户检测成功*/
            $user->userLoginSuccess($manage_u);/*用户登录成功的处理*/
            $m3result->code = 0;
            $m3result->messages = '用户登录成功';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '用户密码错误';
            $m3result->data['validator'] = $validator->messages();
            $m3result->data['user'] = $user->messages();

            if($m3result->data['user']['code'] == 2)
            {
                $m3result->code = 3;
                $m3result->messages = '用户已禁用';
            }
        }
        return $m3result->toJson();
    }
}