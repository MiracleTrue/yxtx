<?php
use Illuminate\Routing\Router;

//php artisan route:list

Admin::registerAuthRoutes();

/*扩展*/
Route::group([
    'prefix' => config('admin.route.prefix'),
    'namespace' => config('admin.route.namespace'),
], function (Router $router)
{
    $router->any('wangEditor/upload', 'WangEditorController@upload');/*WangEditor上传图片*/
});

/*后台基础*/
Route::group([
    'prefix' => config('admin.route.prefix'),
    'namespace' => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router)
{

    $router->get('/', 'HomeController@index');

    /*设置*/
    $router->get('config', 'ConfigController@index');/*详情*/
    $router->post('config/submit', 'ConfigController@submit');/*提交*/

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
    $router->post('withdrawDeposit/weChat', 'WithdrawDepositController@weChat');/*同意提现(微信钱包)*/
    $router->post('withdrawDeposit/unionPay', 'WithdrawDepositController@unionPay');/*同意提现(银联)*/
    $router->post('withdrawDeposit/deny', 'WithdrawDepositController@deny');/*拒绝提现*/
});
