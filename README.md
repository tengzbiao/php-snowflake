## snowflake

Twitter雪花算法PHP版本

## 生成规则

64位标识唯一ID

> 第一位0 - 41位毫秒级时间戳 - 2位数据中心ID - 6位机器ID - 2位扩展位 - 12位毫秒内顺序ID

## 概述

- php-fpm多进程并发导致ID重复，使用基于信号量代替文件锁和redis锁
- ID趋势递增
- 支持4个数据中心、64台机器
- 每毫秒最多可生成4096个ID

## 使用

- 安装sysvsem和sysvshm扩展(以php-7.1.13版本为例)
```
# cd php-7.1.13/ext/sysvsem
# phpize
# configure
# make && make install 

# cd php-7.1.13/ext/sysvshm
# phpize
# configure
# make && make install 

在php.ini增加、然后重启php-fpm
extension=sysvsem.so
extension=sysvshm.so
```
- composer require tengzbiao/php-snowflake
```
<?php
use tengzbiao\Snowflake;

$dataCenter = 1;
$workerID = 1;
$epoch = strtotime('2019-12-01') * 1000; // 初始化开始时间, 一经设置不能更改。
$idWorker = IDWorker::getInstance($dataCenter, $workerID, $epoch);
echo $idWorker->id();
```
