<?php
//
//Route::get('test', 'TestController@Index');
//Route::get('test/add', 'TestController@T_add');
//Route::get('test/list', 'TestController@T_list');
//Route::get('test/update', 'TestController@T_update');
//Route::get('test/delete', 'TestController@T_delete');
//Route::get('test/table', 'TestController@T_table');

/*不需要登录的请求*/
Route::get('login', 'IndexController@Login');/*登录页面 | login */
//Route::any('login/submit', 'IndexController@LoginSubmit');/*登录提交 */
//
///*需要登录的请求*/
//Route::group([], function ()
//{
//    Route::get('/', 'IndexController@Index');
//});





///*规格*/
//Route::any('product/spec/add', 'ProductController@ProductSpecAdd')->name('新增商品规格');/*新增商品规格*/
//Route::any('product/spec/edit', 'ProductController@ProductSpecEdit')->name('编辑商品规格');/*编辑商品规格*/
//Route::any('product/spec/delete', 'ProductController@ProductSpecDelete')->name('删除商品规格');/*删除商品规格*/
///*供应商协议价*/
//Route::get('product/supplier/price/{spec_id}', 'ProductController@ProductSupplierPriceView')->name('规格协议价列表')->where('spec_id', '[0-9]+');/*规格协议价列表 | product_supplier_price */
//Route::any('product/supplier/price/add', 'ProductController@ProductSupplierPriceAdd')->name('新增供应商协议价');/*新增供应商协议价*/
//Route::any('product/supplier/price/edit', 'ProductController@ProductSupplierPriceEdit')->name('编辑供应商协议价');/*编辑供应商协议价*/
//Route::any('product/supplier/price/delete', 'ProductController@ProductSupplierPriceDelete')->name('删除供应商协议价');/*删除供应商协议价*/


//Route::get ('no/privilege','IndexController@NoPrivilege');/*没有权限页面*/
//Route::get ('login','IndexController@Login');/*登录页面*/
//Route::get ('logout','IndexController@Logout');/*退出登录*/
//Route::any('login/submit','IndexController@LoginSubmit');/*登录处理*/
//Route::get ('welcome','IndexController@Welcome');/*我的桌面*/
//Route::get ('language/{lang}','IndexController@SetLanguage');/*当前语言更改*/
//Route::any('tools/image_preview','ToolsController@ImagePreview');/*上传临时图片,方便展示*/
//Route::any('tools/image_save','ToolsController@ImageSave');/*上传图片,永久保存*/
//Route::any('tools/image_attr_save','ToolsController@ImageAttrSave');/*上传属性小图,永久保存*/
//Route::any('setting/menus/get','MenuController@MenusGetOne');/*获取单个栏目信息*/
//Route::any('goods/category/relevance','GoodsController@CategoryGetRelevance');
