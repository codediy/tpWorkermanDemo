<?php
/**
 * Created by PhpStorm.
 * User: GIGABYTE
 * Date: 20.7.9
 * Time: 16:22
 */

namespace app\ws\callback;

class WsHandle
{
    public static $defaultReturn = [
        "cid"      => "",
        "platform" => "",
        "msgType"  => "",
        "fromId"   => "",
        "time"     => "",
        "option"   => [],
    ];

    public static function workerStart($option)
    {
        self::initDefault($option);

        wsSend(self::$defaultReturn);
    }

    public static function workerReload($option)
    {
        self::initDefault($option);

        wsSend(self::$defaultReturn);
    }

    public static function workerConnect($option)
    {
        self::initDefault($option);

        wsSend(self::$defaultReturn);
    }

    public static function workerMessage($option)
    {
        self::initDefault($option);

        wsSend(self::$defaultReturn);
    }

    public static function workerClose($option)
    {
        self::initDefault($option);

        wsSend(self::$defaultReturn);
    }


    private static function initDefault($option)
    {

        self::$defaultReturn["cid"]      = $option["cid"];
        self::$defaultReturn["platform"] = $option["platform"];
        self::$defaultReturn["msgType"]  = $option["msgType"];
        self::$defaultReturn["fromId"]   = $option["fromId"];
        self::$defaultReturn["time"]     = time();
    }


}