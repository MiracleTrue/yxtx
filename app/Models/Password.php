<?php
namespace App\Models;

use Illuminate\Support\Facades\Hash;

/**
 * Class Password 密码编译类
 * @package App\Models
 */
class Password extends Model
{

    /**
     * 生成密码 (Hash)
     * @param $pass
     * @return password & bool
     */
    public function makeHashPassword($pass)
    {
        if (!empty($pass))
        {
            return Hash::make($pass);
        }
        else
        {
            return false;
        }
    }

    /**
     * 验证密码是否正确(Hash)
     * @param $pass & 待验证的密码
     * @param $hash_pass & 数据库中Hash的密码
     * @return bool
     */
    public function checkHashPassword($pass, $hash_pass)
    {
        return Hash::check($pass, $hash_pass);
    }
}