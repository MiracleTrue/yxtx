<?php
namespace App\Models;

use App\Entity\AccountLog;
use App\Entity\MatchRegistration;
use App\Entity\Users;
use App\Entity\WithdrawDeposit;
use Illuminate\Support\Facades\DB;

/**
 * Class Transaction 交易相关模型
 * @package App\Models
 */
class Transaction extends Model
{
    /*提现状态:  0.待审核  1.已通过*/
    const WITHDRAW_DEPOSIT_STATUS_WAIT = 0;
    const WITHDRAW_DEPOSIT_STATUS_AGREE = 1;

    /*账户日志类型:  10.报名付费  20.报名收入  30.提现*/
    const ACCOUNT_LOG_TYPE_REGISTRATION_FEE = 10;
    const ACCOUNT_LOG_TYPE_REGISTRATION_INCOME = 20;
    const ACCOUNT_LOG_TYPE_WITHDRAW_DEPOSIT = 30;

    /**
     * 支付回调地址 (报名参加比赛)
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public static function getRegistrationMatchNotifyUrl()
    {
        return url('wxPayment/registrationMatch');
    }

    /**
     * 报名付费成功
     * @param $order_sn
     * @return bool
     * @throws \Exception
     */
    public function RegistrationMatchPaymentSuccess($order_sn)
    {
        $e_match_registration = MatchRegistration::where('order_sn', $order_sn);

        $e_match_registration->status = Registration::STATUS_WAIT_NUMBER;

        $this->accountLogChange($e_match_registration->user_id, self::ACCOUNT_LOG_TYPE_REGISTRATION_FEE, $e_match_registration->match_info->need_money);

        return true;
    }

    /**
     * 用户账户日志 生成
     * @param $user_id
     * @param $type
     * @param $money
     * @param string $desc
     * @return bool
     * @throws \Exception
     */
    public function accountLogChange($user_id, $type, $money, $desc = '')
    {
        if (in_array($type, [self::ACCOUNT_LOG_TYPE_REGISTRATION_FEE, self::ACCOUNT_LOG_TYPE_REGISTRATION_INCOME, self::ACCOUNT_LOG_TYPE_WITHDRAW_DEPOSIT]))
        {
            $e_account_log = new AccountLog();
            $e_account_log->user_id = $user_id;
            $e_account_log->type = $type;
            $e_account_log->money = $money;
            $e_account_log->desc = $desc;
            $e_account_log->create_time = now();
            $e_account_log->save();
            return true;
        }
        else
        {
            throw new \Exception('账户日志类型不正确');
        }
    }

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
                    $this->accountLogChange($e_withdraw_deposit->user_id, self::ACCOUNT_LOG_TYPE_WITHDRAW_DEPOSIT, bcsub(0, $e_withdraw_deposit->money, 2));
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
     * 返回账户日志类型 的文本名称
     * @param $type
     * @return string
     */
    public static function AccountLogTypeTransformText($type)
    {
        $text = '';
        switch ($type)
        {
            case self::ACCOUNT_LOG_TYPE_WITHDRAW_DEPOSIT:
                $text = '提现';
                break;
            case self::ACCOUNT_LOG_TYPE_REGISTRATION_INCOME:
                $text = '报名收入';
                break;
            case self::ACCOUNT_LOG_TYPE_REGISTRATION_FEE:
                $text = '报名付费';
                break;
        }
        return $text;
    }

    /**
     * 返回提现状态 的文本名称
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