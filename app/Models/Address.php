<?php
namespace App\Models;

use App\Entity\MatchAddress;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

/**
 * Class Address 地址相关模型
 * @package App\Models
 */
class Address extends Model
{
    private $tencent_map_key = 'QESBZ-5WLRD-KKW4Q-HURHH-5ZAJ7-ZEBRO';//腾讯位置服务key

    /**
     * 查询一个比赛地址 (按名称)
     * @param $city_name
     * @return mixed
     */
    public function getMatchAddressFromCity($city_name)
    {
        $match_address = MatchAddress::where('city', $city_name)->first();

        return $match_address;
    }

    /**
     * 新增一个比赛地址
     * @param $province
     * @param $city
     * @param null $district
     * @return MatchAddress
     */
    public function addMatchAddress($province, $city, $district = null)
    {
        $match_address = new MatchAddress();
        $match_address->province = $province;
        $match_address->city = $city;
        $match_address->district = $district;
        $match_address->save();

        return $match_address;
    }

    /**
     * 请求腾讯位置Api 逆地址解析
     * @param $lat
     * @param $lng
     * @return mixed
     */
    public function tencent_coordinateAddressResolution($lat, $lng)
    {
        $client = new Client();
        $response = $client->request('GET', 'http://apis.map.qq.com/ws/geocoder/v1',
            [
                'query' =>
                    [
                        'key' => $this->tencent_map_key,
                        'location' => $lat . ',' . $lng,
                    ]
            ]
        );
        $arr = json_decode($response->getBody()->getContents(), true);

        return $arr;
    }


}