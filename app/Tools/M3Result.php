<?php
/**
 * Created by BocWeb.
 * Author: Walker  QQ:120007700
 * Date  : 2017/10/12
 * Time  : 11:17
 */
namespace App\Tools;

/**
 * Json 响应类
 * Class M3Result
 * @package App\Http\Result
 */
class M3Result{
    /**
     * @var 返回码
     */
    public $code;


    /**
     * @var 文字说明
     */
    public $messages;


    /**
     * @var 返回额外数据
     */
    public $data;

    /**
     * @return json
     */
    public function toJson()
    {
        if($this->data == null) unset($this->data);
        return json_encode($this, JSON_UNESCAPED_UNICODE);
    }
}