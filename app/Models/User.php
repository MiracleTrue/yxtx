<?php
/**
 * Created by BocWeb.
 * Author: Walker  QQ:120007700
 * Date  : 2017/10/12
 * Time  : 13:38
 */

namespace App\Models;

use App\Entity\Users;
use Carbon\Carbon;

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
     * 获取所有日志列表 (已转换:身份标识文本,日志创建时间) (如有where 则加入新的sql条件) "分页" | 默认排序:创建时间
     * @param array $where &  [['users.identity', '=', '2'],['nick_name', 'like', '%:00%']]
     * @return mixed
     */
    public function getLogList($where = array())
    {
        /*预加载ORM对象*/
        $log_list = DB::table('user_log')
            ->join('users', 'user_log.user_id', '=', 'users.user_id')
            ->where($where)
            ->orderBy('user_log.create_time', 'desc')
            ->select('user_log.*', 'users.identity', 'users.nick_name', 'users.phone')
            ->paginate($_COOKIE['PaginationSize']);

        /*数据过滤排版*/
        $log_list->transform(function ($item)
        {
            $item->identity_text = User::identityTransformText($item->identity);
            $item->create_time = Carbon::createFromTimestamp($item->create_time)->toDateTimeString();
            return $item;
        });
        return $log_list;
    }

    /**
     * 获取当前用户日志列表 (日志创建时间) "分页" | 默认排序:创建时间
     * @return mixed
     */
    public function getLogManage()
    {
        /*初始化*/
        $manage_u = session('ManageUser');
        $log_list = UserLog::where('user_id', $manage_u->user_id)->orderBy('create_time', 'desc')->paginate($_COOKIE['PaginationSize']);
        /*数据过滤排版*/
        $log_list->transform(function ($item)
        {
            $item->create_time = Carbon::createFromTimestamp($item->create_time)->toDateTimeString();
            return $item;
        });
        return $log_list;
    }

    /**
     * 获取所有用户列表 (已转换:身份标识文本,创建时间) (如有where 则加入新的sql条件) "分页" | 默认排序:用户ID
     * @param array $where
     * @return mixed
     */
    public function getUserList($where = array())
    {
        /*初始化*/
        $e_users = new Users();

        /*预加载ORM对象*/
        $user_list = $e_users->where($where)->orderBy('user_id', 'desc')->paginate($_COOKIE['PaginationSize']);

        /*数据过滤排版*/
        $user_list->transform(function ($item)
        {
            $item->identity_text = User::identityTransformText($item->identity);
            $item->create_time = Carbon::createFromTimestamp($item->create_time)->toDateTimeString();
            return $item;
        });

        return $user_list;
    }


    /**
     * 获取分配产品有协议价的供货商列表 (已转换:身份标识文本,创建时间)
     * @param $product_name
     * @param $spec_name
     * @return mixed
     */
    public function getAllocationProductSupplierList($product_name, $spec_name)
    {
        /*初始化*/
        $e_product = Product::checkProduct($product_name, $spec_name);
        if(empty($e_product))
        {
            return collect();
        }
        $e_users = new Users();

        /*预加载ORM对象*/
        $supplier_list = $e_users->where('users.is_disable', User::NO_DISABLE)->where('users.identity', User::SUPPLIER_ADMIN)
            ->with(['ho_supplier_price' => function ($query) use($e_product)
            {
                $query->where('spec_id',$e_product->spec_info->spec_id);
            }])->get();

        /*过滤没有该产品协议价的供应商*/
        $supplier_list = $supplier_list->filter(function ($value) {
            return !empty($value->ho_supplier_price);
        });

        /*数据过滤排版*/
        $supplier_list->transform(function ($item)
        {
            $item->identity_text = User::identityTransformText($item->identity);
            $item->create_time = Carbon::createFromTimestamp($item->create_time)->toDateTimeString();
            $item->price_info = $item->ho_supplier_price;
            unset($item->ho_supplier_price);
            return $item;
        });

        return $supplier_list;
    }

    /**
     * 获取所有供货商列表 (已转换:身份标识文本,创建时间)
     * @return mixed
     */
    public function getSupplierList()
    {
        $e_users = new Users();

        /*预加载ORM对象*/
        $supplier_list = $e_users->where('users.is_disable', User::NO_DISABLE)->where('users.identity', User::SUPPLIER_ADMIN)->get();

        /*数据过滤排版*/
        $supplier_list->transform(function ($item)
        {
            $item->identity_text = User::identityTransformText($item->identity);
            $item->create_time = Carbon::createFromTimestamp($item->create_time)->toDateTimeString();
            return $item;
        });

        return $supplier_list;
    }

    /**
     * 获取所有平台运营员列表 (已转换:身份标识文本,创建时间)
     * @return mixed
     */
    public function getPlatformUserList()
    {
        $e_users = new Users();

        /*预加载ORM对象*/
        $user_list = $e_users->where('users.is_disable', User::NO_DISABLE)->where('users.identity', User::PLATFORM_ADMIN)->get();

        /*数据过滤排版*/
        $user_list->transform(function ($item)
        {
            $item->identity_text = User::identityTransformText($item->identity);
            $item->create_time = Carbon::createFromTimestamp($item->create_time)->toDateTimeString();
            return $item;
        });

        return $user_list;
    }

    /**
     * 获取单个用户 (已关联:平台运营员负责分类) (已转换:身份标识文本,创建时间)
     * @param $id
     * @return mixed
     */
    public function getUser($id)
    {
        /*初始化*/
        $e_users = Users::find($id);
        /*转换身份标识文本*/
        $e_users->identity_text = User::identityTransformText($e_users->identity);
        $e_users->create_time = Carbon::createFromTimestamp($e_users->create_time)->toDateTimeString();
        return $e_users;
    }

    /**
     * 用户登录检测与校验 (使用用户名方式登录)
     * @param $name
     * @param $pass
     * @return Users | bool
     */
    public function userLoginFromName($name, $pass)
    {
        /*初始化*/
        $password = new Password();
        $users = Users::where('user_name', $name)->get()->first();

        if (!empty($users))/*检测用户是否存在*/
        {
            if ($users->is_disable == User::NO_DISABLE)/*检测用户是否禁用*/
            {
                if ($password->checkHashPassword($pass, $users->password) === true)/*检测用户密码是否正确*/
                {
                    /*验证成功,返回User对象*/
                    return $users;
                }
                else
                {
                    $this->errors['code'] = 1;
                    $this->errors['messages'] = '用户密码错误';
                    return false;
                }
            }
            else
            {
                $this->errors['code'] = 2;
                $this->errors['messages'] = '用户已禁用';
                return false;
            }
        }

        return false;
    }

    /**
     * 用户登录成功的处理
     * @param $user
     */
    public function userLoginSuccess(Users $user)
    {
        /*加入session*/
        session(['ManageUser' => $user]);
    }

    /**
     * 使用原密码: 修改单个用户密码
     * @param $user_id & 用户id
     * @param $original_password & 原密码
     * @param $new_password & 新密码
     * @return bool
     */
    public function editPasswordOriginal($user_id, $original_password, $new_password)
    {
        /*初始化*/
        $e_users = Users::find($user_id);
        $password = new Password();

        if ($password->checkHashPassword($new_password, $e_users->password) === true)
        {
            $this->errors['code'] = 2;
            $this->errors['messages'] = '新密码不能和原密码相同';
            return false;
        }

        if ($password->checkHashPassword($original_password, $e_users->password) === true)
        {
            $e_users->password = $password->makeHashPassword($new_password) or die();
            $e_users->save();
            User::userLog('使用原密码方式修改密码');

            /*删除管理员的session*/
            session()->forget('ManageUser');
            return true;
        }
        else
        {
            $this->errors['code'] = 1;
            $this->errors['messages'] = '原密码不正确';
            return false;
        }
    }

    /**
     * 添加单个用户
     * @param $arr
     * @return Users
     */
    public function addUser($arr)
    {
        /*初始化*/
        $e_users = new Users();
        $password = new Password();

        /*添加用户*/
        $e_users->user_name = $arr['user_name'];
        $e_users->nick_name = $arr['nick_name'];
        $e_users->identity = $arr['identity'];
        $e_users->password = $password->makeHashPassword($arr['password']) or die();
        $e_users->phone = !empty($arr['phone']) ? $arr['phone'] : '';
        $e_users->is_disable = self::NO_DISABLE;
        $e_users->create_time = Carbon::now()->timestamp;

        $e_users->save();
        User::userLog(User::identityTransformText($e_users->identity) . ': ' . $e_users->nick_name . "($e_users->user_name)");
        return $e_users;
    }

    /**
     *
     * 修改单个用户 (只允许修改:用户姓名,手机号码,用户密码) 密码为空时不修改
     * @param $arr
     * @return bool
     */
    public function editUser($arr)
    {
        /*初始化*/
        $e_users = Users::find($arr['user_id']);
        $password = new Password();

        /*修改用户*/
        $e_users->nick_name = $arr['nick_name'];
        $e_users->phone = !empty($arr['phone']) ? $arr['phone'] : '';

        if (!empty($arr['password']))
        {
            $e_users->password = $password->makeHashPassword($arr['password']) or die();
        }

        $e_users->save();
        User::userLog(User::identityTransformText($e_users->identity) . ': ' . $e_users->nick_name . "($e_users->user_name)");
        return $e_users;
    }

    /**
     * 用户禁用启用开关
     * @param $id
     * @return bool
     */
    public function disableOrEnableUser($id)
    {
        /*初始化*/
        $e_users = Users::find($id);

        /*超级管理员除外*/
        if ($e_users->identity != self::ADMINISTRATOR)
        {
            /*修改用户*/
            if ($e_users->is_disable == self::IS_DISABLE)
            {
                $e_users->is_disable = self::NO_DISABLE;
            }
            else
            {
                $e_users->is_disable = self::IS_DISABLE;
            }

            $e_users->save();
            User::userLog(User::identityTransformText($e_users->identity) . ': ' . $e_users->nick_name . "($e_users->user_name)");
            return true;
        }
        return false;
    }


    /**
     * 用户的操作日志生成,请在模型中调用,并写入描述
     * @param string $desc 描述
     */
    public static function userLog($desc = '')
    {
        /*初始化*/
        $manage_u = session('ManageUser');
        $user_log = new UserLog();

        $route = Route::current();

        /*判断是合法的路由格式*/
        if (!empty($route->action['group']) && !empty($route->action['as']))
        {
            /*拼接log信息的样式*/
            $log_desc = $route->action['group'] . '-' . $route->action['as'] . ' >> ' . $desc;

            /*入库*/
            $user_log->user_id = $manage_u->user_id;
            $user_log->ip_address = request()->getClientIp();
            $user_log->log_desc = $log_desc;
            $user_log->create_time = Carbon::now()->timestamp;
            $user_log->save();
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
                $text='封停';
                break;
            case self::NO_DISABLE:
                $text='正常';
                break;
        }
        return $text;
    }

}