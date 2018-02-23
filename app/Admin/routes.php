<?php
use Illuminate\Routing\Router;

//php artisan route:list

Admin::registerAuthRoutes();

Route::group([
    'prefix' => config('admin.route.prefix'),
    'namespace' => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router)
{

    $router->get('/', 'HomeController@index');

    /*banner图管理*/
    $router->resource('banner', BannerController::class);

    /*会员管理*/
    $router->get('user', 'UserController@index');/*列表*/
    $router->put('user/{user}', 'UserController@update');/*更新*/

    /*比赛管理*/
    $router->get('match', 'MatchController@index');/*列表*/
    $router->get('match/{match_id}', 'MatchController@show');/*详情*/
    $router->get('match/user/{user_id}', 'MatchController@user');/*我的比赛*/
    $router->get('match/map/{match_id}', 'MatchController@map');/*地图*/
    $router->post('match/delete', 'MatchController@delete');/*删除*/

    /*提现管理*/
    $router->get('withdrawDeposit', 'WithdrawDepositController@index');/*列表*/
    $router->post('withdrawDeposit/agree', 'WithdrawDepositController@agree');/*同意提现*/

});
