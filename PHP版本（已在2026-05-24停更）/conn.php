<?php


function connect_db(){

    // 数据库设置
    $config = array(
        'host' => 'localhost',
        'user' => 'root',
        'password' => 'ss123321',
        'dbname' => 'galbase',
        'charset' => 'utf8'
    );



    // 连接数据库
    global $link;
    $link = mysqli_connect($config['host'], $config['user'], $config['password'], $config['dbname']);
    mysqli_set_charset($link, $config['charset']);



    // 判断是否连接到数据库
    if($link){
        // 成功连接到数据库
        return $link;
    }else{
        die('无法连接到数据库'.mysqli_error());
    }
}




// 变量连接数据库
$link = connect_db();