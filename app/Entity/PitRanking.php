<?php

namespace App\Entity;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class PitRanking
 * Table 坑冠排行表
 * @package App\Entity
 */
class PitRanking extends Entity
{

    /*修改器:相册json转换array*/
    public function setMatchPhotosAttribute($photos)
    {
        //['xxx.jpg','aaa.jpg']
        if (is_array($photos))
        {
            $this->attributes['match_photos'] = json_encode($photos);
        }
    }

    public function getMatchPhotosAttribute($photos)
    {
        $arr = json_decode($photos, true);
        if (is_array($arr))
        {
            return $arr;
        }
        else
        {
            return array();
        }
    }

    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'pit_ranking';

    /**
     * 可以通过 $primaryKey 属性，重新定义主键字段
     * @var string
     */
    protected $primaryKey = 'id';

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
     * 一对一关联MatchAddress实体表
     */
    public function address_info()
    {
        return $this->hasOne(MatchAddress::class, 'address_id', 'address_id');
    }

}
