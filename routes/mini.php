<?php
use App\Http\Middleware\WxAppKeyCheck;
use App\Http\Middleware\UserBindPhoneCheck;

Route::any('test', 'TestController@test');


Route::group(['middleware' => [WxAppKeyCheck::class]], function ()
{
    /*不需要登录的请求*/
    Route::any('login', 'UserController@login');/*登录*/
    Route::any('index/banner', 'IndexController@banner');/*首页banner图列表*/
    Route::any('index/bannerDetail', 'IndexController@bannerDetail');/*首页banner图详情*/
    Route::any('index/match', 'IndexController@match');/*首页比赛列表*/
    Route::any('index/search', 'IndexController@search');/*比赛搜索列表*/
    Route::any('location/serviceCity', 'LocationController@serviceCity');/*获取服务开通城市*/
    Route::any('match/info', 'MatchController@info');/*获取比赛详情*/

    /*需要登录的请求*/
    Route::any('match/cash/registrationDetail', 'MatchController@cashRegistrationDetail');/*现金报名详情*/
    Route::any('match/cash/getNumber', 'MatchController@cashGetNumber');/*现金报名抽取号码*/
    Route::any('match/cash/allNumber', 'MatchController@cashAllNumber');/*现金报名一键抽号*/

    Route::any('match/info/registrationDetail', 'MatchController@registrationDetail');/*比赛报名详情*/
    Route::any('match/info/numberDetail', 'MatchController@numberDetail');/*比赛抽号详情*/
    Route::any('match/getNumber', 'MatchController@getNumber');/*已报名抽取号码*/
    Route::any('match/openNumber', 'MatchController@openNumber');/*比赛开启抽号*/
    Route::any('match/uploadPhoto', 'MatchController@uploadPhoto');/*比赛图片上传*/
    Route::any('user/bindPhone', 'UserController@bindPhone');/*绑定手机*/
    Route::any('user/smsCode', 'UserController@smsCode');/*获取短信验证码*/
    Route::any('user/locationSet', 'UserController@locationSet');/*更改服务城市*/
    Route::any('user/info', 'UserController@info');/*获取当前用户详情*/
    Route::any('user/myMatch', 'UserController@myMatch');/*我发布的比赛*/
    Route::any('user/myRegistration', 'UserController@myRegistration');/*我报名的比赛*/
    Route::any('user/withdraw/weChat', 'UserController@withdrawWeChat');/*用户提现(微信钱包)*/
    Route::any('user/withdraw/unionPay', 'UserController@withdrawUnionPay');/*用户提现(银联)*/
    Route::any('user/accountHistory', 'UserController@accountHistory');/*用户账户流水*/

    /*需要登录并绑定手机的请求*/
    Route::group(['middleware' => [UserBindPhoneCheck::class]], function ()
    {
        Route::any('match/cash/registration', 'MatchController@cashRegistration');/*现金参加比赛*/

        Route::any('match/release', 'MatchController@release');/*比赛发布*/
        Route::any('match/registration', 'MatchController@registration');/*报名参加比赛*/
        Route::any('match/delete', 'MatchController@delete');/*删除未报名比赛*/
    });
});

Route::any('wxPayment/registrationMatch', 'WeChatController@registrationMatchPaymentSuccess');


