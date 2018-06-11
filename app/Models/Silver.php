<?php
namespace App\Models;

use App\Entity\SilverExchange;
use Illuminate\Support\Facades\DB;

/**
 * Class Silver 银币相关模型
 * @package App\Models
 */
class Silver extends Model
{
    /*兑换申请状态 10.待兑换  20.兑换成功*/
    const EXCHANGE_STATUS_WAIT = 10;
    const EXCHANGE_STATUS_SUCCESS = 20;


    /**
     * 返回兑换状态 的文本名称
     * @param $status
     * @return string
     */
    public static function exchangeStatusTransformText($status)
    {
        $text = '';
        switch ($status)
        {
            case self::EXCHANGE_STATUS_WAIT:
                $text = '待兑换';
                break;
            case self::EXCHANGE_STATUS_SUCCESS:
                $text = '兑换成功';
                break;
        }
        return $text;
    }

    /**
     * 同意兑换
     * @param $id
     * @return bool
     * @throws \Throwable
     */
    public function agreeExchange($id)
    {
        /*事物*/
        try
        {
            DB::transaction(function () use ($id)
            {
                $e_silver_exchange = SilverExchange::lockForUpdate()->where('id',$id)->where('status',self::EXCHANGE_STATUS_WAIT)->firstOrFail();

                $e_silver_exchange->status = self::EXCHANGE_STATUS_SUCCESS;
                $e_silver_exchange->save();
            });
        } catch (\Exception $e)
        {
            $this->errors['code'] = 1;
            $this->errors['messages'] = $e->getMessage();
            return false;
        }
        return true;
    }
}