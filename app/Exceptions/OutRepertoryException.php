<?php
namespace App\Exceptions;

use Exception;

class OutRepertoryException extends Exception
{

    /**
     * SupplierPriceNotFindException constructor.
     */
    public function __construct()
    {
        parent::__construct('出库接口异常');
    }

    /**
     * 报告异常(内部日志处理)
     */
    public function report()
    {

    }

    /**
     * 将异常渲染到 HTTP 响应中。
     * @param $request
     */
    public function render($request)
    {
//        return response(...);
    }
}