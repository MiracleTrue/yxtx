<?php

namespace App\Admin\Extensions;

use Encore\Admin\Form\Field;

class Tencent_Map extends Field
{
    protected $view = 'admin.tencent_map';

    protected static $css = [
        // '/vendor/wangEditor-3.0.9/release/wangEditor.min.css',
    ];

    protected static $js = [
        '/vendor/tencent-map/jsv2.js',
    ];

    public function __construct($column, $arguments)
    {
        $this->column['lat'] = $column;
        $this->column['lng'] = $arguments[0];

    dump($arguments);

        array_shift($arguments);

        dd($arguments);

        $this->label = $this->formatLabel($arguments);
        $this->id = $this->formatId($this->column);


        //$this->render();
    }

    public function render()
    {

        $this->script = <<<EOT
        function initTencentMap(name) {
            var lat = $('#{$this->id['lat']}').val();
            var lng = $('#{$this->id['lng']}').val();

            var center = new qq.maps.LatLng(lat, lng);

            var container = document.getElementById("map_"+name);
            var map = new qq.maps.Map(container, {
                center: center,
                zoom: 13
            });

            var marker = new qq.maps.Marker({
                position: center,
                draggable: true,
                map: map
            });

//            if( ! lat || ! lng) {
//                var citylocation = new qq.maps.CityService({
//                    complete : function(result){
//                    console.log(result);
//                        map.setCenter(result.detail.latLng);
//                        marker.setPosition(result.detail.latLng);
//                    }
//                });
//
//                citylocation.searchLocalCity();
//            }

            qq.maps.event.addListener(map, 'click', function(event) {
                marker.setPosition(event.latLng);
            });
        }

        initTencentMap('{$this->id['lat']}{$this->id['lng']}');
EOT;
        return parent::render();
    }
}