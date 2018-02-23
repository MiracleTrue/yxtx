<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;

class Ajax_Delete
{
    protected $url;

    protected $id;

    public function __construct($url, $id)
    {
        $this->id = $id;
        $this->url = $url;
    }

    protected function script()
    {

        $deleteConfirm = trans('admin.delete_confirm');
        $confirm = trans('admin.confirm');
        $cancel = trans('admin.cancel');

        return <<<SCRIPT

$('.grid-delete-row').unbind('click').click(function() {

    var id = $(this).data('id');

    swal({
      title: "$deleteConfirm",
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
            data: {
                _token:LA.token,
                id:id
            },
            success: function (data) {
                $.pjax.reload('#pjax-container');

                if (typeof data === 'object') {
                
                    if (data.code == 0) {
                        swal(data.messages, '', 'success');
                    } else {
                        swal(data.messages, '', 'error');
                    }
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

        return "<a class='btn btn-sm btn-danger grid-delete-row' style='margin-right: 10px' data-id='{$this->id}'><i class='fa fa-trash-o'></i> 删除</a>";
    }

    public function __toString()
    {
        return $this->render();
    }
}