<?php
namespace App\Models;

use App\Entity\MatchAddress;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

/**
 * Class Location 地址位置相关模型
 * @package App\Models
 */
class Location extends Model
{
    /*比赛地址表,是否开通服务 1.是  0.否*/
    const MATCH_ADDRESS_IS_SERVICE = 1;
    const MATCH_ADDRESS_NO_SERVICE = 0;

    private $tencent_map_key = 'QESBZ-5WLRD-KKW4Q-HURHH-5ZAJ7-ZEBRO';//腾讯位置服务key

    /**
     * 去除城市名中的"市"字
     * @param $city_name
     * @return mixed
     */
    public function cityToSimple($city_name)
    {
        $simple_name = str_replace('市', '', $city_name);
        return $simple_name;
    }

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
        $match_address->create_time = now();
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