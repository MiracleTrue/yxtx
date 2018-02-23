<?php

namespace App\Entity;

use App\Models\Match;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class MatchList
 * Table 赛事发布表
 * @package App\Entity
 */
class MatchList extends Entity
{
    /**
     * 数据模型的启动方法
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        /**
         * 全局作用域
         * 比赛删除状态
         * is_delete
         */
        static::addGlobalScope('is_delete', function (Builder $builder)
        {
            $builder->where('is_delete', Match::NO_DELETE);
        });
    }

    /*修改器:相册json转换*/
    public function setMatchPhotosAttribute($photos)
    {
        if (is_array($photos))
        {
            $this->attributes['pictures'] = json_encode($photos);
        }
    }

    public function getMatchPhotosAttribute($photos)
    {
        return json_decode($photos, true);
    }

    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'match_list';

    /**
     * 可以通过 $primaryKey 属性，重新定义主键字段
     * @var string
     */
    protected $primaryKey = 'match_id';

    /**
     * 默认情况下，Eloquent预计数据表中有 "created_at" & "updated_at" 字段。
     * 不希望Eloquent字段维护这2个字段，可设置：$timestamps = false
     * @var bool
     */
    public $timestamps = false;

    /**
     * 需要自定义时间戳格式，可在模型内设置 $dateFormat 属性（决定了日期如何在数据库中存储，以及当模型被序列化成数组或JSON时的格式）
     * 格式为 date() 函数第一个参数，详情看手册
     * @var string
     */
    protected $dateFormat = "Y-m-d H:i:s";

    /**
     * 一对一关联Users实体表
     */
    public function release_user()
    {
        return $this->hasOne(Users::class, 'user_id', 'user_id');
    }

    /**
     * 一对多关联MatchRegistration实体表
     */
    public function reg_list()
    {
        return $this->hasMany(MatchRegistration::class, 'match_id');
    }

    //    /**
    //     * 一对多关联ProductsCategoryManage实体表
    //     */
    //    public function hm_product_category_manage()
    //    {
    //        return $this->hasMany(ProductsCategoryManage::class, 'user_id');
    //    }
    //
    //    /**
    //     * 一对多关联SupplierPrice实体表
    //     */
    //    public function hm_supplier_price()
    //    {
    //        return $this->hasMany(SupplierPrice::class, 'user_id');
    //    }
    //
    //    /**
    //     * 一对多关联SupplierPrice实体表
    //     */
    //    public function ho_supplier_price()
    //    {
    //        return $this->hasOne(SupplierPrice::class, 'user_id', 'user_id');
    //    }

}
