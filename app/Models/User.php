<?php

namespace App\Models;

use App\Entity\WxAppkey;
use App\Entity\WxOpenid;
use Illuminate\Support\Facades\Crypt;

/**
 * Class User 用户相关模型
 * @package App\Models
 */
class User extends Model
{
    /*禁用状态:  1.禁用  0.启用*/
    const IS_DISABLE = 1;
    const NO_DISABLE = 0;

    public function wxRegister()
    {

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