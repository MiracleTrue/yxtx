<?php

namespace App\Models;

use App\Entity\SmsCode;
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

    public function getUserInfo($user_id)
    {
        $location = new Location();
        $e_users = Users::find($user_id);
        $e_users->location_simple = $location->cityToSimple($e_users->location);

        return $e_users;
    }

    /**
     * 用户设置使用位置
     * @param $user_id
     * @param $city
     * @return mixed
     */
    public function locationSet($user_id, $city)
    {
        $e_users = Users::find($user_id);

        $e_users->location = $city;
        $e_users->save();
        return $e_users;
    }

    /**
     * 生成短信验证码
     * @param $phone
     * @param null $valid_date
     * @return int
     */
    public function makeSmsCode($phone, $valid_date = null)
    {
        $date = $valid_date == null ? now()->addMinutes(5) : $valid_date;

        $code = mt_rand(100000, 999999);
        SmsCode::where('phone', $phone)->delete();

        $e_sms_code = new SmsCode();
        $e_sms_code->phone = $phone;
        $e_sms_code->sms_code = $code;
        $e_sms_code->valid_date = $date;
        $e_sms_code->save();

        return $code;
    }

    /**
     * 检测短信验证码是否有效
     * @param $phone
     * @param $code
     * @return bool
     */
    public function checkSmsCode($phone, $code)
    {
        $e_sms_code = SmsCode::where('phone', $phone)->where('sms_code', $code)->where('valid_date', '>', now())->first();

        if ($e_sms_code == null)
        {
            return false;
        }
        else
        {
            $e_sms_code->delete();
            return true;
        }
    }

    /**
     * 给一个用户绑定手机号码
     * @param $user_id
     * @param $phone
     * @return bool
     */
    public function bindPhone($user_id, $phone)
    {
        $e_users = Users::find($user_id);
        if ($e_users == null)
        {
            return false;
        }
        else
        {
            $e_users->phone = $phone;
            $e_users->save();
            return true;
        }
    }

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
                $e_users->location = '青岛市';
                $e_users->create_time = now();
                $e_users->save();
            });
        } catch (\Exception $e)
        {
            \Log::emergency($decryptData);
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