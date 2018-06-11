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

    /*首页banner图管理*/
    $router->resource('banner', BannerController::class);

    /*排行banner图管理*/
    $router->resource('rankingBanner', RankingBannerController::class);

    /*会员管理*/
    $router->get('user', 'UserController@index');/*列表*/
    $router->put('user/{user}', 'UserController@update');/*更新*/

    /*比赛管理*/
    $router->get('match', 'MatchController@index');/*列表*/
    $router->get('match/{match_id}', 'MatchController@show');/*详情*/
    $router->get('match/user/{user_id}', 'MatchController@user');/*我的比赛*/
    $router->get('match/map/{match_id}', 'MatchController@map');/*地图*/
    $router->post('match/delete', 'MatchController@delete');/*删除*/

    /*坑冠排行管理*/
    $router->resource('pitRanking', PitRankingController::class);

    /*金币管理*/
    $router->resource('goldGoods', GoldGoodsController::class);/*金币商城*/
    $router->get('goldExchange', 'GoldExchangeController@index');/*金币兑换申请*/
    $router->get('goldExchange/{id}', 'GoldExchangeController@show');/*金币兑换申请详情*/
    $router->post('goldExchange/exchange', 'GoldExchangeController@exchange');/*金币兑换同意申请*/

    /*银币管理*/
    $router->resource('silverGoods', SilverGoodsController::class);/*银币商城*/
    $router->get('silverExchange', 'SilverExchangeController@index');/*银币兑换申请*/
    $router->get('silverExchange/{id}', 'SilverExchangeController@show');/*银币兑换申请详情*/
    $router->post('silverExchange/exchange', 'SilverExchangeController@exchange');/*银币兑换同意申请*/

    /*提现管理*/
    $router->get('withdrawDeposit', 'WithdrawDepositController@index');/*列表*/
    $router->post('withdrawDeposit/weChat', 'WithdrawDepositController@weChat');/*同意提现(微信钱包)*/
    $router->post('withdrawDeposit/unionPay', 'WithdrawDepositController@unionPay');/*同意提现(银联)*/
    $router->post('withdrawDeposit/deny', 'WithdrawDepositController@deny');/*拒绝提现*/
});
