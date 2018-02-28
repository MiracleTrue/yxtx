<?php
use App\Http\Middleware\WxAppKeyCheck;
use App\Http\Middleware\UserBindPhoneCheck;

Route::any('test', 'TestController@test');





Route::group(['middleware' => [WxAppKeyCheck::class]], function ()
{
    /*不需要登录的请求*/
    Route::any('login', 'UserController@login');/*登录*/
    Route::any('index/banner', 'IndexController@banner');/*首页banner图*/
    Route::any('index/match', 'IndexController@match');/*首页比赛列表*/
    Route::any('index/search', 'IndexController@search');/*比赛搜索列表*/
    Route::any('location/serviceCity', 'LocationController@serviceCity');/*获取服务开通城市*/

    /*需要登录的请求*/
    Route::any('user/bindPhone', 'UserController@bindPhone');/*绑定手机*/
    Route::any('user/smsCode', 'UserController@smsCode');/*获取短信验证码*/
    Route::any('user/locationSet', 'UserController@locationSet');/*更改服务城市*/
});

/*需要登录并绑定手机的请求*/
Route::group(['middleware' => [WxAppKeyCheck::class, UserBindPhoneCheck::class]], function ()
{
    Route::any('match/release', 'MatchController@release');/*比赛发布*/
    Route::any('match/uploadPhoto', 'MatchController@uploadPhoto');/*比赛图片上传*/
});

Route::any('wechat', 'WeChatController@serve');
