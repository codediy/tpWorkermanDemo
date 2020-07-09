<?php

namespace app\ws\controller;

/*ws数据查看*/
use app\ws\callback\WsHandle;
use think\facade\Validate;

class Index
{
    /*消息转发处理*/
    public function index()
    {
        $post  = request()->param();
        $rules = [
            "workerType" => "require" /*请求类型*/
        ];

        $validate = Validate::make($rules);

        if ($validate->check($post)) {
            switch ($post["workerType"]) {
                case "onWorkerStart" :
                    WsHandle::workerStart($post);
                    break;
                case "onWorkerReload" :
                    WsHandle::workerReload($post);
                    break;
                case "onConnect" :
                    WsHandle::workerConnect($post);
                    break;
                case "onMessage" :
                    WsHandle::workerMessage($post);
                    break;
                case "onClose" :
                    WsHandle::workerClose($post);
                    break;
            }
        }
    }

}
