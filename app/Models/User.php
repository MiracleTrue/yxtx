<?php

namespace App\Models;

use App\Entity\Users;
use App\Entity\WxAppkey;
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

    /**
     * 微信用户注册
     * @param $decryptData
     * @return bool
     */
    public function wxRegister($decryptData)
    {
        /*事物*/
        try
        {
            DB::transaction(function () use ($decryptData)
            {
                $e_users = new Users();
                $e_users->openid = $decryptData['openId'];
                $e_users->nick_name = $decryptData['nickName'];
                $e_users->avatar = $decryptData['avatarUrl'];
                $e_users->user_money = 0;
                $e_users->freeze_money = 0;
                $e_users->create_time = now();
                $e_users->save();
            });
        } catch (\Exception $e)
        {
            $this->errors['code'] = 1;
            $this->errors['messages'] = '注册失败';
            return false;
        }
        return true;
    }

    /**
     * 检测微信openid是否已存在
     * @param $openid
     * @return bool
     */
    public function wxCheckOpenid($openid)
    {
        $e_users = Users::where('openid', $openid)->first();

        if ($e_users == null)
        {
            return false;
        }
        else
        {
            return $e_users;
        }
    }

    /**
     * 生成微信用户凭证key
     * @param $openid
     * @param $session_key
     * @return string
     */
    public function wxAppkey($openid, $session_key)
    {
        $e_wx_appkey = new WxAppkey();
        $app_key = Crypt::encryptString($session_key);
        $e_wx_appkey->app_key = $app_key;
        $e_wx_appkey->session_key = $session_key;
        $e_wx_appkey->openid = $openid;
        $e_wx_appkey->valid_time = now()->addHours(2);
        $e_wx_appkey->save();

        return $app_key;
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