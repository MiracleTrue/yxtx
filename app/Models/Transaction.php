<?php

namespace App\Models;

use App\Entity\Users;
use App\Entity\WithdrawDeposit;
use App\Exceptions\NetworkBusyException;
use Illuminate\Support\Facades\DB;


/**
 * Class Transaction 交易相关模型
 * @package App\Models
 */
class Transaction extends Model
{
    /*提现状态:  0.待审核  1.已通过*/
    const WITHDRAW_DEPOSIT_STATUS_WAIT  = 0;
    const WITHDRAW_DEPOSIT_STATUS_AGREE = 1;


    /**
     * 同意一个用户的提现申请
     * @param $id
     * @return bool
     * @throws \Throwable
     */
    public function agreeWithdrawDeposit($id)
    {
        /*事物*/
        try
        {
            DB::transaction(function () use ($id)
            {
                $e_withdraw_deposit = WithdrawDeposit::lockForUpdate()->where('id', $id)->where('status', self::WITHDRAW_DEPOSIT_STATUS_WAIT)->firstOrFail();

                if (bcsub($e_withdraw_deposit->user_info->freeze_money, $e_withdraw_deposit->money, 2) < 0)
                {
                    throw new \Exception('金额数据异常');
                }
                else
                {
                    $e_withdraw_deposit->status = self::WITHDRAW_DEPOSIT_STATUS_AGREE;
                    $e_withdraw_deposit->save();
                    Users::where('user_id', $e_withdraw_deposit->user_id)->update(['freeze_money' => bcsub($e_withdraw_deposit->user_info->freeze_money, $e_withdraw_deposit->money, 2)]);
                }
            });
        } catch (\Exception $e)
        {
            $this->errors['code'] = 1;
            $this->errors['messages'] = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * 返回报名状态 的文本名称
     * @param $status
     * @return string
     */
    public static function withdrawDepositStatusTransformText($status)
    {
        $text = '';
        switch ($status)
        {
            case self::WITHDRAW_DEPOSIT_STATUS_WAIT:
                $text = '待审核';
                break;
            case self::WITHDRAW_DEPOSIT_STATUS_AGREE:
                $text = '已通过';
                break;
        }
        return $text;
    }

}