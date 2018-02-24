<?php

Route::any('login', 'LoginController@login');/*微信登录*/


Route::any('wechat', 'WeChatController@serve');


