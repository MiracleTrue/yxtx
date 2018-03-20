<?php
namespace App\Models;

use App\Entity\AccountLog;
use App\Entity\MatchRegistration;
use App\Entity\Users;
use App\Entity\WithdrawDeposit;
use Illuminate\Support\Facades\DB;
use EasyWeChat\Factory;

/**
 * Class Transaction 交易相关模型
 * @package App\Models
 */
class Transaction extends Model
{
    /*提现类型:  1.微信钱包  2.银联*/
    const WITHDRAW_DEPOSIT_TYPE_WECHAT = 1;
    const WITHDRAW_DEPOSIT_TYPE_UNIONPAY = 2;

    /*提现状态:  0.待审核  1.已通过*/
    const WITHDRAW_DEPOSIT_STATUS_WAIT = 0;
    const WITHDRAW_DEPOSIT_STATUS_AGREE = 1;

    /*账户日志类型:  10.报名付费  20.报名收入  30.提现*/
    const ACCOUNT_LOG_TYPE_REGISTRATION_FEE = 10;
    const ACCOUNT_LOG_TYPE_REGISTRATION_INCOME = 20;
    const ACCOUNT_LOG_TYPE_WITHDRAW_DEPOSIT = 30;

    /**
     * 获取所有账户日志列表 (如有where 则加入新的sql条件) "分页" | 默认排序:创建时间
     * @param array $where
     * @param array $orderBy
     * @param bool $is_paginate
     * @param bool $is_whereIn
     * @param null $whereIn
     * @return AccountLog
     */
    public function getAccountLog($where = array(), $orderBy = array(['account_log.create_time', 'desc']), $is_paginate = true, $is_whereIn = false, $whereIn = null)
    {
        /*初始化*/
        $e_account_log = new AccountLog();

        /*预加载ORM对象*/
        $e_account_log = $e_account_log->where($where);
        foreach ($orderBy as $value)
        {
            $e_account_log->orderBy($value[0], $value[1]);
        }
        /*是否使用whereIn*/
        if ($is_whereIn === true)
        {
            $e_account_log->whereIn($whereIn[0], $whereIn[1]);
        }

        /*是否需要分页*/
        if ($is_paginate === true)
        {
            $e_account_log = $e_account_log->paginate($_COOKIE['PaginationSize']);
        }
        else
        {
            $e_account_log = $e_account_log->get();
        }

        /*数据过滤*/
        $e_account_log->transform(function ($item)
        {
            $item->type_text = self::AccountLogTypeTransformText($item->type);
            return $item;
        });

        return $e_account_log;
    }

    /**
     * 报名付费 (生成微信支付)
     * @param $reg_id
     * @return string
     */
    public function registrationMatchWxPayStart($reg_id)
    {
        $app = app('wechat.payment');
        $session_user = session('User');
        $registration = new Registration();
        $reg_info = $registration->getRegistrationInfo($reg_id);

        $result = $app->order->unify([
            'body' => $reg_info->match_info->title,
            'out_trade_no' => $reg_info->order_sn,
            'total_fee' => bcmul($reg_info->match_info->need_money, 100),
            'notify_url' => url('wxPayment/registrationMatch'), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'JSAPI',
            'openid' => $session_user->openid,
        ]);

        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS')
        {
            $prepayId = $result['prepay_id'];
            $config = $app->jssdk->sdkConfig($prepayId); // 返回数组
            return $config;
        }
        else
        {
            info('微信支付失败:' . collect($result));
            return '';
        }
    }

    /**
     * 报名付费成功,改变订单状态
     * @param $reg_id
     * @return bool
     * @throws \Exception
     */
    public function registrationMatchPaySuccess($reg_id)
    {
        /*事物*/
        try
        {
            DB::transaction(function () use ($reg_id)
            {
                $e_match_registration = MatchRegistration::where('reg_id', $reg_id)->where('status', Registration::STATUS_WAIT_PAYMENT)->firstOrFail();

                $e_match_registration->status = Registration::STATUS_WAIT_NUMBER;

                $e_match_registration->save();

                $this->accountLogChange($e_match_registration->user_id, self::ACCOUNT_LOG_TYPE_REGISTRATION_FEE, bcsub(0, $e_match_registration->match_info->need_money, 2));

                /*收款用户信息改变*/
                $e_users = Users::findOrFail($e_match_registration->match_info->user_id);
                $last_money = bcsub($e_match_registration->match_info->need_money, bcmul($e_match_registration->match_info->need_money, 0.01, 2), 2);
                $e_users->user_money = bcadd($e_users->user_money, $last_money, 2);
                $e_users->save();
                $this->accountLogChange($e_users->user_id, self::ACCOUNT_LOG_TYPE_REGISTRATION_INCOME, $last_money);
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
     * 用户申请提现(微信钱包)
     * @param $user_id
     * @param $money
     * @return bool
     */
    public function userWithdrawWeChat($user_id, $money)
    {
        /*事物*/
        try
        {
            DB::transaction(function () use ($user_id, $money)
            {
                $e_withdraw_deposit = new WithdrawDeposit();
                $info = [
                    'order_sn' => $this::makeOrderSn(),
                ];

                $e_withdraw_deposit->user_id = $user_id;
                $e_withdraw_deposit->status = self::WITHDRAW_DEPOSIT_STATUS_WAIT;
                $e_withdraw_deposit->money = $money;
                $e_withdraw_deposit->info = $info;
                $e_withdraw_deposit->create_time = now();
                $e_withdraw_deposit->save();

                $e_users = Users::find($user_id);
                $e_users->user_money = bcsub($e_users->user_money, $money, 2);
                $e_users->freeze_money = bcadd($e_users->freeze_money, $money, 2);
                $e_users->save();
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
     * 用户申请提现(银联)
     * @param $user_id
     * @param $money
     * @param $account
     * @param $name
     * @param $bank
     * @return bool
     */
    public function userWithdrawUnionPay($user_id, $money, $account, $name, $bank)
    {
        /*事物*/
        try
        {
            DB::transaction(function () use ($user_id, $money, $account, $name, $bank)
            {
                $e_withdraw_deposit = new WithdrawDeposit();
                $info = [
                    'account' => $account,
                    'name' => $name,
                    'bank' => $bank,
                ];

                $e_withdraw_deposit->user_id = $user_id;
                $e_withdraw_deposit->type = self::WITHDRAW_DEPOSIT_TYPE_UNIONPAY;
                $e_withdraw_deposit->status = self::WITHDRAW_DEPOSIT_STATUS_WAIT;
                $e_withdraw_deposit->money = $money;
                $e_withdraw_deposit->info = $info;
                $e_withdraw_deposit->create_time = now();
                $e_withdraw_deposit->save();

                $e_users = Users::find($user_id);
                $e_users->user_money = bcsub($e_users->user_money, $money, 2);
                $e_users->freeze_money = bcadd($e_users->freeze_money, $money, 2);
                $e_users->save();
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
     * 同意一个用户的提现申请(微信钱包)
     * @param $id
     * @return bool
     * @throws \Throwable
     */
    public function agreeWithdrawWeChat($id)
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
     * 同意一个用户的提现申请(银联)
     * @param $id
     * @return bool
     * @throws \Throwable
     */
    public function agreeWithdrawUnionPay($id)
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
     * 返回提现类型 的文本名称
     * @param $type
     * @return string
     */
    public static function withdrawDepositTypeTransformText($type)
    {
        $text = '';
        switch ($type)
        {
            case self::WITHDRAW_DEPOSIT_TYPE_WECHAT:
                $text = '微信钱包';
                break;
            case self::WITHDRAW_DEPOSIT_TYPE_UNIONPAY:
                $text = '银联';
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