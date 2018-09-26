<?php

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations\Collection;

use MongoDB\Collection;
use Exception;
use MongoDB\Driver\Command;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class Statistics
{
    const COUNT = 'count';
    const SIZE = 'size';
    const AVG_OBJ_SIZE = 'avgObjSize';
    const STORAGE_SIZE = 'storageSize';
    const NUM_EXTENTS = 'numExtents';
    const NINDEXES = 'nindexes';
    const LAST_EXTENT_SIZE = 'lastExtentSize';
    const PADDING_FACTOR = 'paddingFactor';
    const TOTAL_INDEX_SIZE = 'totalIndexSize';

    public static $metrics = [
        self::COUNT,
        self::SIZE,
        self::AVG_OBJ_SIZE,
        self::STORAGE_SIZE,
        self::NUM_EXTENTS,
        self::NINDEXES,
        self::LAST_EXTENT_SIZE,
        self::PADDING_FACTOR,
        self::TOTAL_INDEX_SIZE,
    ];

    /**
     * @var \MongoDB\Collection
     */
    private $collection;

    /**
     * @var array
     */
    private $before = [];

    /**
     * @var array
     */
    private $after = [];

    /**
     * @param \MongoDB\Collection
     */
    public function setCollection(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return \MongoDB\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    public function updateBefore()
    {
        $data = $this->getCollectionStats();
        foreach ($data as $key => $value) {
            if (in_array($key, static::$metrics)) {
                $this->before[$key] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getBefore()
    {
        return $this->before;
    }

    public function updateAfter()
    {
        $data = $this->getCollectionStats();
        foreach ($data as $key => $value) {
            if (in_array($key, static::$metrics)) {
                $this->after[$key] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * @return array
     *
     * @throws \MongoDB\Driver\Exception\Exception
     */
    protected function getCollectionStats()
    {
        $manager = $this->collection->getManager();
        $name = $this->collection->getCollectionName();

        $cursor = $manager->executeCommand(
            $this->collection->getDatabaseName(),
            new Command(['collStats' => $name])
        );
        $result = $cursor->toArray();
        if (empty($result)) {
            $message = sprintf(
                'Statistics not found for collection %s',
                $name
            );
            throw new Exception($message);
        }

        $data = (array) $result[0];

        if (isset($data['errmsg'])) {
            throw new Exception($data['errmsg']);
        }

        return $data;
    }
}
