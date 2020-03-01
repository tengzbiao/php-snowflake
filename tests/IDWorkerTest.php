<?php

use PHPUnit\Framework\TestCase;
use tengzbiao\Snowflake\IDWorker;

class IDWorkerTest extends TestCase
{
    public function testID()
    {
        $arr = [];
        $epoch = time() * 1000;
        $idWorker = IDWorker::getInstance(1, 1, $epoch);
        for ($i = 1; $i <= 10000; $i++) {
            $id = $idWorker->id();
            if (isset($arr[$id])) {
                $arr[$id] += 1;
            } else {
                $arr[$id] = 1;
            }
        }

        foreach ($arr as $k => $v) {
            if ($v > 1) {
                print_r("$k => $v\n");
            }
        }

        $this->expectOutputString("10000");

        print_r((string)count($arr));

    }
}