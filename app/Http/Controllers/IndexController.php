<?php
namespace App\Http\Controllers;
use App\Models\Transaction;

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

        dd(11);
        return view('index', $this->ViewData);
    }

    public function Login()
    {

        $a  = new Transaction();
        $a->agreeWithdrawDeposit(2);
        
    }

}