<?php

namespace tengzbiao\Snowflake;

class IDWorker
{
    const TIMESTAMP_BITS = 41;
    const WORKER_BITS = 6;
    const DATA_CENTER_BITS = 2;
    const EXTENSION_BITS = 2;
    const SEQUENCE_BITS = 12;

    private $timestampShift = self::SEQUENCE_BITS + self::EXTENSION_BITS + self::WORKER_BITS + self::DATA_CENTER_BITS;
    private $dataCenterShift = self::SEQUENCE_BITS + self::EXTENSION_BITS + self::WORKER_BITS;
    private $workerShift = self::SEQUENCE_BITS + self::EXTENSION_BITS;
    private $extensionShirt = self::SEQUENCE_BITS;
    private $workerMax = -1 ^ (-1 << self::WORKER_BITS);
    private $dataCenterMax = -1 ^ (-1 << self::DATA_CENTER_BITS);
    private $sequenceMax = -1 ^ (-1 << self::SEQUENCE_BITS);
    private $extensionMax = -1 ^ (-1 << self::EXTENSION_BITS);

    private static $ins;
    private $workerID; // 节点ID
    private $dataCenterID; // 数据中心ID
    private $timestamp; // 上一次时间
    private $epoch = 1576051466689; // 初始时间2019-12-11 12:04:43，这个一旦定义且开始生成ID后千万不要改了，不然可能会生成相同的ID
    private $extension = 0;

    private function __construct($dataCenterID, $workerID, int $epoch)
    {
        if ($dataCenterID > $this->dataCenterMax) {
            throw new IDWorkerException("data center id should between 0 and " . $this->dataCenterMax);
        }

        if ($workerID > $this->workerMax) {
            throw new IDWorkerException("worker id should between 0 and " . $this->workerID);
        }

        $epochMax = $this->getUnixTimestamp();
        $epochMin = $epochMax - strtotime("1 year");
        if ($epoch > $epochMax || $epoch < $epochMin) {
            throw new IDWorkerException(sprintf("epoch should between %s and %s", $epochMin, $epochMax));
        }

        $this->dataCenterID = $dataCenterID;
        $this->workerID = $workerID;
        $this->epoch = $epoch;
    }

    public static function getInstance($dataCenterID, $workerID, int $epoch)
    {
        if (is_null(self::$ins)) {
            self::$ins = new self($dataCenterID, $workerID, $epoch);
        }
        return self::$ins;
    }

    public function id()
    {
        $timestamp = $this->getUnixTimestamp();
        // 允许时钟回拨
        if ($timestamp < $this->timestamp) {
            $diff = $this->timestamp - $timestamp;
            if ($diff < 2) {
                sleep($diff);
                $timestamp = $this->getUnixTimestamp();
                if ($timestamp < $this->timestamp) {
                    $this->extension += 1;
                    if ($this->extension > $this->extensionMax) {
                        throw new IDWorkerException("clock moved backwards");
                    }
                }
            } else {
                $this->extension += 1;
                if ($this->extension > $this->extensionMax) {
                    throw new IDWorkerException("clock moved backwards");
                }
            }
        }

        $sequenceID = $this->getSequenceID();
        if ($sequenceID > $this->sequenceMax) {
            $timestamp = $this->getUnixTimestamp();
            while ($timestamp <= $this->timestamp) {
                $timestamp = $this->getUnixTimestamp();
            }
            $sequenceID = $this->getSequenceID();
        }
        $this->timestamp = $timestamp;
        $id = (int)($timestamp - $this->epoch) << $this->timestampShift
            | $this->dataCenterID << $this->dataCenterShift
            | $this->workerID << $this->workerShift
            | $this->extension << $this->extensionShirt
            | $sequenceID;

        return (string)$id;
    }

    private function getUnixTimestamp()
    {
        return floor(microtime(true) * 1000);
    }

    private function getSequenceID($max = 4096, $min = 0)
    {
        $key = ftok(__FILE__, 'd');
        $var_key = 100;
        $sem_id = sem_get($key);
        $shm_id = shm_attach($key, 4096);
        $cycle_id = 0;

        if (sem_acquire($sem_id)) {
            $cycle_id = intval(@shm_get_var($shm_id, $var_key) ?: 0);
            $cycle_id++;
            if ($cycle_id > $max) {
                $cycle_id = $min;
            }
            shm_put_var($shm_id, $var_key, $cycle_id);
            shm_detach($shm_id);
            sem_release($sem_id);
        }
        return $cycle_id;
    }

    private function __clone()
    {

    }
}