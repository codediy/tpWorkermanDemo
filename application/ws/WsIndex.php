<?php

namespace app\ws;

use think\Db;
use think\facade\Cache;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class WsIndex
{
    /*客户端链接worker*/
    public static $wsUrl = "ws://demo.com:8787";
    /*Worker发送个WsCall*/
    public static $ioUrl = "http://demo.com/ws/index";
    /*WsCall发送Worker*/
    public static $innerUrl = 'http://0.0.0.0:7878';

    /**
     * @var Worker
     */
    private static $workerInstance;
    // 客户端请求
    public static $reqKey = ["platform", "msgType", "fromId", "time", "option"];
    // Worker<->WsCall的请求
    public static $reqOrResCallKey = ["cid", "platform", "msgType", "fromId", "time", "option"];

    //消息类型
    const USER_MSG_CHECK = -1;  /*检查参数*/
    const USER_OPEN_CONNECT = 1; /*打开链接*/
    const USER_CLOSE_CONNECT = 2; /*关闭链接*/

    //响应状态
    const RES_MSG_NO = -1; /*错误*/
    const RES_MSG_OK = 1; /*正常*/

    // cid 到 uid
    public static $cidToUid = [];

    // uid_msgType 到 cid
    public static $waitReadNumId = [];

    /**
     * @info Worker启动回调
     * @param $work
     * @param $type
     * @throws \Exception
     */
    public static function workerHandle(&$work, $type)
    {
        if ($type == "onWorkerStart") {
            self::$workerInstance = $work;
            self::reqCall($type);
        }

        if ($type == "onWorkerReload") {
            self::$workerInstance = $work;
            self::reqCall($type);
        }
        // 接受WsCall的消息
        $inner_text_worker            = new Worker(self::$innerUrl);
        $inner_text_worker->onMessage = function ($con, $rawData) {
            $data        = isset($rawData["post"]) ? $rawData["post"] : [];
            $checkResult = self::checkCallMsg($data);

            if ($checkResult["status"] == "ok"
                && isset($data["msgType"])
                && isset($data["cid"])
            ) {
                self::callRes($data);
            }
        };
        $inner_text_worker->listen();

    }

    /**
     * @info 链接处理回调
     * @param $con TcpConnection
     * @param $type "onConnect"|"onClose"
     */
    public static function connectHandle(&$con, $type)
    {
        if ($type == "onConnect") {
            self::reqCall($type, ["cid" => $con->id]);
        }

        if ($type == "onClose") {
            self::reqCall($type, ["cid" => $con->id]);
            //删除关联信息
            self::rmCid($con->id);
        }
    }

    /**
     * @info 消息处理
     * @param $con TcpConnection
     * @param $rawData
     */
    public static function msgHandle(&$con, $rawData)
    {
        $data        = json_decode($rawData, true);
        $checkResult = self::checkMsg($data);

        if ($checkResult["status"] == "ok") {
            if (isset($data["msgType"])) {
                //添加id关联
                self::uidWithCid($data["fromId"], $data["msgType"], $con);

                $callData        = $rawData;
                $callData["cid"] = $con->id;
                self::reqCall("onMessage", $callData);
            }
        } else {
            $result = $checkResult["data"];
            self::returnJson($con, $result);
        }
    }


    public static function errorHandle()
    {

    }

    /*请求WsCall*/
    public static function reqCall($type, $data = [])
    {
        $data["workerType"] = $type;
        $checkResult        = self::checkCallMsg($data);

        if ($checkResult["status"] == "ok") {
            httpPost(self::$ioUrl, $data);
        }
    }

    /*WsCall响应处理*/
    public static function callRes($data)
    {
        /*读取cid的uid*/
        $uid = self::$cidToUid[$data["cid"]];

        /*多个客户端链接 uid_msgType -> cid */
        $uidCons = isset(self::$waitReadNumId[$uid . "_" . $data["msgType"]])
            ? self::$waitReadNumId[$uid . "_" . $data["msgType"]]
            : [];

        foreach ($uidCons as $k => $v) {

            if (isset(self::$workerInstance->connections[$k])) {
                self::returnJson(
                    self::$workerInstance->connections[$k],
                    self::getResData(
                        self::RES_MSG_OK,
                        $data["msgType"],
                        $data["option"]
                    )
                );
            } else {
                //断开链接
                self::rmCid($k);
            }
        }
    }

    /**
     * @info 检查传入的消息字段
     * @param $data
     * @return array
     */
    private static function checkMsg($data)
    {
        $return = [
            "status" => "ok",
            "msg"    => "检查通过",
            "data"   => []
        ];

        //检查字段
        foreach (self::$reqKey as $k => $v) {
            if (!isset($data[$v])) {
                $return["status"] = "no";
                //消息类型
                $return["data"] = self::getResData(
                    self::USER_MSG_CHECK,
                    self::USER_MSG_CHECK,
                    ["msg" => "消息格式错误:缺少参数{$v}"]
                );
                break;
            }
        }
        return $return;
    }

    /*wsCall的响应消息*/
    private static function checkCallMsg($data)
    {
        $return = [
            "status" => "ok",
            "msg"    => "检查通过",
            "data"   => []
        ];

        //检查字段
        foreach (self::$reqOrResCallKey as $k => $v) {
            if (!isset($data[$v])) {
                $return["status"] = "no";
                //消息类型
                $return["data"] = self::getResData(
                    self::USER_MSG_CHECK,
                    self::USER_MSG_CHECK,
                    ["msg" => "消息格式错误:缺少参数{$v}"]
                );
                break;
            }
        }
        return $return;
    }

    /**
     * @info 生成返回消息
     * @param $status int 响应状态
     * @param $msgType  int 消息类型
     * @param array $option 其他参数
     * @return array 返回响应内容
     */
    private static function getResData($status, $msgType, $option = [])
    {
        return [
            "status"  => $status,
            "msgType" => $msgType,
            "time"    => time(),
            "option"  => $option //扩展参数
        ];
    }


    /**
     * @info 注册uid于与cid关联信息
     * @param $uid int 用户id
     * @param $con TcpConnection 链接
     */
    private static function uidWithCid($uid, $msgType, &$con)
    {
        /*cid到uid*/
        self::$cidToUid[$con->id] = $uid;

        /*uid_msgType到cid*/
        self::$waitReadNumId[$uid . "_" . $msgType][$con->id] = true;
    }


    /**
     * @info  删除链接id的相关信息
     * @param $cid int 链接id
     */
    private static function rmCid($cid)
    {
        $uid = isset(self::$cidToUid[$cid]) ? self::$cidToUid[$cid] : 0;
        if ($uid > 0) {
            //删除注册的数组
            $uidCons = [self::$waitReadNumId];

            foreach ($uidCons as $k => $v) {
                if (isset($v[$uid]) && isset($v[$uid][$cid])) {
                    unset($v[$uid][$cid]);
                }
            }
        }
    }

    /**
     * @info  返回消息给客户端
     * @param $con TcpConnection
     * @param $data
     */
    private static function returnJson(&$con, $data)
    {
        $con->send(json_encode($data, JSON_UNESCAPED_UNICODE));
    }


}
