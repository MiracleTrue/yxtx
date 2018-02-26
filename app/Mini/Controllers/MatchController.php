<?php
namespace App\Mini\Controllers;

use App\Models\Match;
use App\Tools\M3Result;
use Illuminate\Http\Request;

/**
 * 比赛 控制器
 * Class MatchController
 * @package App\Mini\Controllers
 */
class MatchController extends Controller
{

    public function release(Request $request)
    {
        /*初始化*/
        $session_user = session('User');
        $match = new Match();


        dd($session_user, $request->all());
    }

}