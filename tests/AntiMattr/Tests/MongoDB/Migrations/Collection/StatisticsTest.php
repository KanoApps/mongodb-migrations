<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Collection;

use AntiMattr\MongoDB\Migrations\Collection\Statistics;
use PHPUnit\Framework\TestCase;

class StatisticsTest extends TestCase
{
    private $collection;
    private $statistics;

    protected function setUp()
    {
        $this->collection = $this->createMock('MongoDB\Collection');
        $this->statistics = new Statistics();
    }

    public function testSetGetCollection()
    {
        $this->statistics->setCollection($this->collection);
        $this->assertEquals($this->collection, $this->statistics->getCollection());
    }

    /**
     * @expectedException \Exception
     */
    public function testGetCollectionStatsThrowsExceptionWhenDataNotFound()
    {
        $this->statistics = new StatisticsStub();
        $this->statistics->setCollection($this->collection);

        $cursorStub = $this->createMock(MongoDbCursorStub::class);
        $cursorStub->expects($this->once())
            ->method('ToArray')
            ->will($this->returnValue([['errmsg' => 'foo']]));

        $managerStub = $this->createMock(MongoDbManagerStub::class);
        $managerStub->expects($this->once())
            ->method('executeCommand')
            ->willReturn($cursorStub);

        // hack in a Manager stub
        $this->collection->expects($this->once())
            ->method('getManager')
            ->willReturn($managerStub);

        $this->collection->expects($this->once())
            ->method('getCollectionName')
            ->will($this->returnValue('example'));

        $this->statistics->doGetCollectionStats();
    }

    /**
     * @expectedException \Exception
     */
    public function testGetCollectionStatsThrowsExceptionWhenErrmsgFound()
    {
        $this->statistics = new StatisticsStub();
        $this->statistics->setCollection($this->collection);

        $cursorStub = $this->createMock(MongoDbCursorStub::class);

        $managerStub = $this->createMock(MongoDbManagerStub::class);
        $managerStub->expects($this->once())
            ->method('executeCommand')
            ->willReturn($cursorStub);

        $this->collection->expects($this->once())
            ->method('getCollectionName')
            ->will($this->returnValue('example'));

        // hack in a Manager stub
        $this->collection->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($managerStub));

        $this->expectException('Exception');

        $this->statistics->doGetCollectionStats();
    }

    public function testGetCollectionStats()
    {
        $this->statistics = new StatisticsStub();
        $this->statistics->setCollection($this->collection);

        $expectedData = [
            'count' => 100,
        ];

        // collstats command returns an array with a single object in it now
        $stats = new \stdClass();
        $stats->count = 100;

        $cursorStub = $this->createMock(MongoDbCursorStub::class);
        $cursorStub->expects($this->once())
            ->method('ToArray')
            ->will($this->returnValue([$stats]));

        $managerStub = $this->createMock(MongoDbManagerStub::class);
        $managerStub->expects($this->once())
            ->method('executeCommand')
            ->will($this->returnValue($cursorStub));

        $this->collection->expects($this->once())
            ->method('getManager')
            ->willReturn($managerStub);

        $this->collection->expects($this->once())
            ->method('getCollectionName')
            ->will($this->returnValue('example'));

        $data = $this->statistics->doGetCollectionStats();

        $this->assertSame($expectedData, $data);
    }
}

class MongoDbManagerStub
{
    public function executeCommand()
    {
        return new MongoDbCursorStub();
    }
}

class MongoDbCursorStub
{
    public function ToArray()
    {
        return [];
    }
}

class StatisticsStub extends Statistics
{
    public function doGetCollectionStats()
    {
        return $this->getCollectionStats();
    }
}
