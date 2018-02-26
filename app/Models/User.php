<?php

namespace App\Models;

use App\Entity\Users;
use App\Entity\WxAppkey;
use App\Entity\WxOpenid;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Class User 用户相关模型
 * @package App\Models
 */
class User extends Model
{
    /*禁用状态:  1.禁用  0.启用*/
    const IS_DISABLE = 1;
    const NO_DISABLE = 0;

    public function wxRegister($decryptData)
    {
        /*事物*/
        try
        {
            DB::transaction(function () use ($decryptData)
            {
                $e_wx_openid = new WxOpenid();
                $e_users = new Users();
                $e_users->nick_name = $decryptData['nickName'];
                $e_users->avatar = $decryptData['avatarUrl'];
                $e_users->user_money = 0;
                $e_users->freeze_money = 0;
                $e_users->create_time = now();
                $e_users->save();

                $e_wx_openid->openid = $decryptData['openId'];
                $e_wx_openid->user_id = $e_users->user_id;
                $e_wx_openid->save();
            });
        } catch (\Exception $e)
        {
            $this->errors['code'] = 1;
            $this->errors['messages'] = '注册失败';
            return false;
        }
        return true;
    }


    public function wxCheckOpenid($openid)
    {
        $e_wx_openid = WxOpenid::find($openid);

        if ($e_wx_openid == null)
        {
            return false;
        }
        else
        {
            return $e_wx_openid;
        }
    }

    public function wxAppkey($openid, $session_key)
    {
        $e_wx_appkey = new WxAppkey();
        $e_wx_appkey->app_key = Crypt::encryptString($session_key);
        $e_wx_appkey->session_key = $session_key;
        $e_wx_appkey->openid = $openid;
        $e_wx_appkey->valid_time = now()->addHours(2);
        $e_wx_appkey->save();
        return $e_wx_appkey;
    }

    public function wxLogin($openid, $session_key)
    {
        if ($e_wx_openid = self::wxCheckOpenid($openid))
        {
            $e_users = $e_wx_openid->user_info;
            if ($e_users->is_disable == self::IS_DISABLE)
            {
                return false;
            }
            else
            {
                return self::wxAppkey($openid, $session_key);
            }
        }
        else
        {
            self::wxRegister();
            return self::wxAppkey($openid, $session_key);
        }
    }


    /**
     * 返回用户禁用状态 的文本名称
     * @param $is_disable
     * @return string
     */
    public static function isDisableTransformText($is_disable)
    {
        $text = '';
        switch ($is_disable)
        {
            case self::IS_DISABLE:
                $text = '封停';
                break;
            case self::NO_DISABLE:
                $text = '正常';
                break;
        }
        return $text;
    }

}