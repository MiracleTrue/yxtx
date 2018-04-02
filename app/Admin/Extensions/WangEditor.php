<?php

namespace App\Admin\Extensions;

use Encore\Admin\Form\Field;

class WangEditor extends Field
{
    protected $view = 'admin.wang-editor';

    protected static $css = [
        '/vendor/wangEditor-3.1.0/release/wangEditor.min.css',
        '/vendor/wangEditor-3.1.0/release/wangEditor-fullscreen-plugin.css',
    ];

    protected static $js = [
        '/vendor/wangEditor-3.1.0/release/wangEditor.min.js',
        '/vendor/wangEditor-3.1.0/release/wangEditor-fullscreen-plugin.js',
    ];

    public function render()
    {
        $name = $this->formatName($this->column);

        $this->script = <<<EOT

var E = window.wangEditor
var editor = new E('#{$this->id}');
editor.customConfig.zIndex = 0;
editor.customConfig.uploadImgServer = '/admin/wangEditor/upload';  // 上传图片到服务器
editor.customConfig.uploadFileName = 'image';
editor.customConfig.uploadImgMaxLength = 1;   // 限制一次最多上传 5 张图片

// 自定义菜单配置
editor.customConfig.menus = [
    'head',  // 标题
    'bold',  // 粗体
    'fontSize',  // 字号
    'fontName',  // 字体
    'italic',  // 斜体
    'underline',  // 下划线
    'strikeThrough',  // 删除线
    'foreColor',  // 文字颜色
    'backColor',  // 背景颜色
//    'link',  // 插入链接
//    'list',  // 列表
    'justify',  // 对齐方式
//    'quote',  // 引用
//    'emoticon',  // 表情
    'image',  // 插入图片
    'table',  // 表格
//    'video',  // 插入视频
//    'code',  // 插入代码
    'undo',  // 撤销
    'redo'  // 重复
]

editor.customConfig.onchange = function (html) {
    $('input[name=$name]').val(html);
}
editor.create();
E.fullscreen.init('#{$this->id}');

EOT;
        return parent::render();
    }
}