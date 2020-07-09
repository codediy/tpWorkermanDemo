<?php

function mapTest()
{
    $a = [1,2,3,4,5];
    /*返回array1的处理后的所有元素*/
    $b = array_map(function($d){
       return $d*$d;
    },$a);
    var_dump($a);
    var_dump($b);
}

function walkTest()
{
    $fruits = [
        "d" => "lemon",
        "a" => "orange",
        "b" => "banana"
    ];

    array_walk($fruits,function($item,$key){
        echo $key."_".$item;
    });

    var_dump($fruits);
}

function walkTest2()
{
    $fruits = [
        "d" => "lemon",
        "a" => "orange",
        "b" => "banana"
    ];
    $arrayKey = ["d","a"];
    $result = [];

    array_walk($fruits,function($item,$key,$resultKey) use (&$result){
        if (in_array($key,$resultKey)){
            $result[] = [$key,$item];
        }

    },$arrayKey);

    var_dump($fruits);
    var_dump($result);
}
walkTest2();
//walkTest();
//mapTest();
