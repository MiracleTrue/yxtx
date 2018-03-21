<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;

class Ajax_DIY2
{
    protected $url;

    protected $field;

    protected $button;

    public function __construct($url, $field = array(), $button = '提交')
    {
        $this->url = $url;
        $this->field = $field;
        $this->button = $button;
    }

    protected function script()
    {

        $submitConfirm = $this->button . '?';
        $confirm = trans('admin.confirm');
        $cancel = trans('admin.cancel');

        return <<<SCRIPT

$('.grid-diy2-row').unbind('click').click(function() {
    var data = {
        _token:LA.token,
    }
    for(item in $(this).data())
    {
        eval("data."+item+"="+$(this).data(item));
    }

    swal({
      title: "$submitConfirm",
      type: "warning",
      showCancelButton: true,
      confirmButtonColor: "#DD6B55",
      confirmButtonText: "$confirm",
      closeOnConfirm: false,
      cancelButtonText: "$cancel"
    },
    function(){
        $.ajax({
            method: 'post',
            url: '{$this->url}',
            dataType:"json",   //返回格式为json
            data: data,
            success: function (data) {
                $.pjax.reload('#pjax-container');
                
                if (typeof data === 'object') {
                    swal(data.messages, '');
                }
            }
        });
    });
});

SCRIPT;
    }

    protected function render()
    {
        Admin::script($this->script());
        $s_text = "<a class='btn btn-sm btn-primary grid-diy2-row' style='margin-right: 10px' ";
        foreach ($this->field as $key => $value)
        {
            $s_text .= "data-{$key}='{$value}'";
        }
        $e_text = "><i class='fa fa-hand-pointer-o'></i> {$this->button}</a>";

        return $s_text . $e_text;
    }

    public function __toString()
    {
        return $this->render();
    }
}