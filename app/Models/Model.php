<?php
namespace App\Models;

use App\Tools\M3Result;

/**
 * Class Model 基础模型
 * @package App\Models
 */
class Model
{
    protected $errors = array('code' => 0, 'messages' => 'OK'); /*错误信息*/

    /**
     * 生成唯一订单号
     * @return string
     */
    public function makeOrderSn()
    {
        $time = explode(" ", microtime());
        $time = $time[1] . ($time[0] * 1000);
        $time = explode(".", $time);
        $time = isset($time[1]) ? $time[1] : 0;
        $time = date('YmdHis') + $time;

        return $time . str_pad(mt_rand(1, 99999), 6, '0', STR_PAD_LEFT);
    }


    /**
     * 根据请求方式,返回不同的"没有"权限的信息
     * @param $request
     */
    public static function noPrivilegePrompt($request)
    {
        if ($request->method() == 'GET')/*页面*/
        {
            die('没有权限访问');
        }
        elseif ($request->method() == 'POST')/*Json*/
        {
            $m3result = new M3Result();
            $m3result->code = -1;
            $m3result->messages = '没有权限访问';
            die($m3result->toJson());
        }
        else
        {
            die('没有权限访问');
        }
    }

    /**
     * 返回 模型 发生的错误信息
     * @return mixed
     */
    public function messages()
    {
        return $this->errors;
    }

    /*单价方式*/
//$calculated_price = floatval(sprintf("%.4f", $price));;/*保留4位小数的单价(舍去法 取4位浮点数)*/
//$calculated_total = bcmul($calculated_price, $e_orders->product_number, 2);/*保留2位小数的总价(舍去法 取2位浮点数)*/

    /*总价方式*/
//$calculated_total = round($total_price, 2);/*保留2位小数的总价(小数第3位四舍五入)*/
//$calculated_price = bcdiv($total_price, $e_orders->product_number, 4);/*保留4位小数的单价(舍去法 取4位浮点数)*/


}