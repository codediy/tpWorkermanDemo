<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Env;
use app\ws\WsIndex;
// +----------------------------------------------------------------------
// | Workerman设置 仅对 php think worker:server 指令有效
// +----------------------------------------------------------------------
return [
    // 扩展自身需要的配置
    'protocol'       => 'websocket', // 协议 支持 tcp udp unix http websocket text
    'host'           => '0.0.0.0', // 监听地址
    'port'           => 8786, // 监听端口
    'socket'         => '', // 完整监听地址
    'context'        => [
    ], // socket 上下文选项
    'worker_class'   => '', // 自定义Workerman服务类名 支持数组定义多个服务

    // 支持workerman的所有配置参数
    'name'           => 'thinkphp',
    'count'          => 1,
    'daemonize'      => false,
    'pidFile'        => Env::get('runtime_path') . 'wsWorker.pid',

    // 支持事件回调
    // onWorkerStart
    'onWorkerStart'  => function ($worker) {
        WsIndex::workerHandle($worker, "onWorkerStart");
    },
    // onWorkerReload
    'onWorkerReload' => function ($worker) {
        WsIndex::workerHandle($worker, "onWorkerReload");
    },
    // onConnect
    'onConnect'      => function ($connection) {
        WsIndex::connectHandle($connection, "onConnect");
    },
    // onMessage
    'onMessage'      => function ($connection, $data) {
        dump($data);
        WsIndex::msgHandle($connection, $data);
    },
    // onClose
    'onClose'        => function ($connection) {
        WsIndex::connectHandle($connection, "onClose");
    },
    // onError
    'onError'        => function ($connection, $code, $msg) {
        echo "error [ $code ] $msg\n";
    },
];
