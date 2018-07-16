<?php
namespace App\Models;

use App\Entity\AccountLog;
use App\Entity\GoldLog;
use App\Entity\MatchRegistration;
use App\Entity\SilverLog;
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

    /*提现状态:  0.待审核  1.已通过  2.已拒绝*/
    const WITHDRAW_DEPOSIT_STATUS_WAIT = 0;
    const WITHDRAW_DEPOSIT_STATUS_AGREE = 1;
    const WITHDRAW_DEPOSIT_STATUS_DENY = 2;

    /*账户日志类型:  10.报名付费  20.报名收入  30.提现*/
    const ACCOUNT_LOG_TYPE_REGISTRATION_FEE = 10;
    const ACCOUNT_LOG_TYPE_REGISTRATION_INCOME = 20;
    const ACCOUNT_LOG_TYPE_WITHDRAW_DEPOSIT = 30;

    /*金币账户日志类型:  10.发布坑冠增加  110.兑换商品减少*/
    const GOLD_LOG_TYPE_RELEASE_PIT = 10;
    const GOLD_LOG_TYPE_EXCHANGE = 110;

    /*银币账户日志类型:  10.发布比赛增加  110.兑换商品减少*/
    const SILVER_LOG_TYPE_RELEASE_MATCH = 10;
    const SILVER_LOG_TYPE_EXCHANGE = 110;

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
     * 获取所有金币账户日志列表 (如有where 则加入新的sql条件) "分页" | 默认排序:创建时间
     * @param array $where
     * @param array $orderBy
     * @param bool $is_paginate
     * @param bool $is_whereIn
     * @param null $whereIn
     * @return AccountLog
     */
    public function getGoldLog($where = array(), $orderBy = array(['gold_log.created_at', 'desc']), $is_paginate = true, $is_whereIn = false, $whereIn = null)
    {
        /*初始化*/
        $e_gold_log = new GoldLog();

        /*预加载ORM对象*/
        $e_gold_log = $e_gold_log->where($where);
        foreach ($orderBy as $value)
        {
            $e_gold_log->orderBy($value[0], $value[1]);
        }
        /*是否使用whereIn*/
        if ($is_whereIn === true)
        {
            $e_gold_log->whereIn($whereIn[0], $whereIn[1]);
        }

        /*是否需要分页*/
        if ($is_paginate === true)
        {
            $e_gold_log = $e_gold_log->paginate($_COOKIE['PaginationSize']);
        }
        else
        {
            $e_gold_log = $e_gold_log->get();
        }

        /*数据过滤*/
        $e_gold_log->transform(function ($item)
        {
            $item->type_text = self::goldLogTypeTransformText($item->type);
            return $item;
        });

        return $e_gold_log;
    }

    /**
     * 获取所有银币账户日志列表 (如有where 则加入新的sql条件) "分页" | 默认排序:创建时间
     * @param array $where
     * @param array $orderBy
     * @param bool $is_paginate
     * @param bool $is_whereIn
     * @param null $whereIn
     * @return AccountLog
     */
    public function getSilverLog($where = array(), $orderBy = array(['silver_log.created_at', 'desc']), $is_paginate = true, $is_whereIn = false, $whereIn = null)
    {
        /*初始化*/
        $e_silver_log = new SilverLog();

        /*预加载ORM对象*/
        $e_silver_log = $e_silver_log->where($where);
        foreach ($orderBy as $value)
        {
            $e_silver_log->orderBy($value[0], $value[1]);
        }
        /*是否使用whereIn*/
        if ($is_whereIn === true)
        {
            $e_silver_log->whereIn($whereIn[0], $whereIn[1]);
        }

        /*是否需要分页*/
        if ($is_paginate === true)
        {
            $e_silver_log = $e_silver_log->paginate($_COOKIE['PaginationSize']);
        }
        else
        {
            $e_silver_log = $e_silver_log->get();
        }

        /*数据过滤*/
        $e_silver_log->transform(function ($item)
        {
            $item->type_text = self::silverLogTypeTransformText($item->type);
            return $item;
        });

        return $e_silver_log;
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
    public static function accountLogChange($user_id, $type, $money, $desc = '')
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
     * 用户金币账户日志 生成
     * @param $user_id
     * @param $type
     * @param $point
     * @param string $desc
     * @return bool
     * @throws \Exception
     */
    public static function goldLogChange($user_id, $type, $point, $desc = '')
    {
        if (in_array($type, [self::GOLD_LOG_TYPE_EXCHANGE, self::GOLD_LOG_TYPE_RELEASE_PIT]))
        {
            $e_gold_log = new GoldLog();
            $e_gold_log->user_id = $user_id;
            $e_gold_log->type = $type;
            $e_gold_log->point = $point;
            $e_gold_log->desc = $desc;
            $e_gold_log->save();
            return true;
        }
        else
        {
            throw new \Exception('金币账户日志类型不正确');
        }
    }

    /**
     * 用户银币账户日志 生成
     * @param $user_id
     * @param $type
     * @param $point
     * @param string $desc
     * @return bool
     * @throws \Exception
     */
    public static function silverLogChange($user_id, $type, $point, $desc = '')
    {
        if (in_array($type, [self::SILVER_LOG_TYPE_EXCHANGE, self::SILVER_LOG_TYPE_RELEASE_MATCH]))
        {
            $e_silver_log = new SilverLog();
            $e_silver_log->user_id = $user_id;
            $e_silver_log->type = $type;
            $e_silver_log->point = $point;
            $e_silver_log->desc = $desc;
            $e_silver_log->save();
            return true;
        }
        else
        {
            throw new \Exception('银币账户日志类型不正确');
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
                $e_withdraw_deposit->type = self::WITHDRAW_DEPOSIT_TYPE_WECHAT;
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
     * 拒绝一个用户的提现申请
     * @param $id
     * @return bool
     */
    public function denyWithdraw($id)
    {
        /*事物*/
        try
        {
            DB::transaction(function () use ($id)
            {
                $e_withdraw_deposit = WithdrawDeposit::lockForUpdate()->where('id', $id)->where('status', self::WITHDRAW_DEPOSIT_STATUS_WAIT)->firstOrFail();

                $e_withdraw_deposit->status = self::WITHDRAW_DEPOSIT_STATUS_DENY;
                $e_withdraw_deposit->save();

                Users::where('user_id', $e_withdraw_deposit->user_id)->update(
                    [
                        'freeze_money' => bcsub($e_withdraw_deposit->user_info->freeze_money, $e_withdraw_deposit->money, 2),
                        'user_money' => bcadd($e_withdraw_deposit->user_info->user_money, $e_withdraw_deposit->money, 2),
                    ]
                );
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

                    /*微信企业付款*/
                    $app = app('wechat.payment');

                    $result = $app->transfer->toBalance([
                        'partner_trade_no' => $e_withdraw_deposit->info['order_sn'], // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
                        'openid' => $e_withdraw_deposit->user_info->openid,
                        'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
                        're_user_name' => '', // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
                        'amount' => bcmul($e_withdraw_deposit->money, 100), // 企业付款金额，单位为分
                        'desc' => 'yxtx用户提取报名费', // 企业付款操作说明信息。必填
                    ]);

                    if ($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS')
                    {
                        info('微信企业付款失败:' . collect($result));
                        throw new \Exception($result['err_code_des']);
                    }

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
                $e_withdraw_deposit = WithdrawDeposit::lockForUpdate()->where('id', $id)->where('type', Transaction::WITHDRAW_DEPOSIT_TYPE_UNIONPAY)
                    ->where('status', self::WITHDRAW_DEPOSIT_STATUS_WAIT)->firstOrFail();

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
     * 返回金币账户日志类型 的文本名称
     * @param $type
     * @return string
     */
    public static function goldLogTypeTransformText($type)
    {
        $text = '';
        switch ($type)
        {
            case self::GOLD_LOG_TYPE_RELEASE_PIT:
                $text = '发布坑冠增加';
                break;
            case self::GOLD_LOG_TYPE_EXCHANGE:
                $text = '兑换商品减少';
                break;
        }
        return $text;
    }

    /**
     * 返回银币账户日志类型 的文本名称
     * @param $type
     * @return string
     */
    public static function silverLogTypeTransformText($type)
    {
        $text = '';
        switch ($type)
        {
            case self::SILVER_LOG_TYPE_RELEASE_MATCH:
                $text = '发布比赛增加';
                break;
            case self::SILVER_LOG_TYPE_EXCHANGE:
                $text = '兑换商品减少';
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
            case self::WITHDRAW_DEPOSIT_STATUS_DENY:
                $text = '已拒绝';
                break;
        }
        return $text;
    }

}