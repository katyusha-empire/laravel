<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\TestCase;

class SupportLazyCollectionTest extends TestCase
{
    public function testCanCreateEmptyCollection()
    {
        $this->assertSame([], LazyCollection::make()->all());
        $this->assertSame([], LazyCollection::empty()->all());
    }

    public function testCanCreateCollectionFromArray()
    {
        $array = [1, 2, 3];

        $data = LazyCollection::make($array);

        $this->assertSame($array, $data->all());

        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $data = LazyCollection::make($array);

        $this->assertSame($array, $data->all());
    }

    public function testCanCreateCollectionFromArrayable()
    {
        $array = [1, 2, 3];

        $data = LazyCollection::make(Collection::make($array));

        $this->assertSame($array, $data->all());

        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $data = LazyCollection::make(Collection::make($array));

        $this->assertSame($array, $data->all());
    }

    public function testCanCreateCollectionFromClosure()
    {
        $data = LazyCollection::make(function () {
            yield 1;
            yield 2;
            yield 3;
        });

        $this->assertSame([1, 2, 3], $data->all());

        $data = LazyCollection::make(function () {
            yield 'a' => 1;
            yield 'b' => 2;
            yield 'c' => 3;
        });

        $this->assertSame([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ], $data->all());
    }

    public function testEager()
    {
        $source = [1, 2, 3, 4, 5];

        $data = LazyCollection::make(function () use (&$source) {
            yield from $source;
        })->eager();

        $source[] = 6;

        $this->assertSame([1, 2, 3, 4, 5], $data->all());
    }

    public function testRemember()
    {
        $source = [1, 2, 3, 4];

        $collection = LazyCollection::make(function () use (&$source) {
            yield from $source;
        })->remember();

        $this->assertSame([1, 2, 3, 4], $collection->all());

        $source = [];

        $this->assertSame([1, 2, 3, 4], $collection->all());
    }

    public function testRememberWithTwoRunners()
    {
        $source = [1, 2, 3, 4];

        $collection = LazyCollection::make(function () use (&$source) {
            yield from $source;
        })->remember();

        $a = $collection->getIterator();
        $b = $collection->getIterator();

        $this->assertEquals(1, $a->current());
        $this->assertEquals(1, $b->current());

        $b->next();

        $this->assertEquals(1, $a->current());
        $this->assertEquals(2, $b->current());

        $b->next();

        $this->assertEquals(1, $a->current());
        $this->assertEquals(3, $b->current());

        $a->next();

        $this->assertEquals(2, $a->current());
        $this->assertEquals(3, $b->current());

        $a->next();

        $this->assertEquals(3, $a->current());
        $this->assertEquals(3, $b->current());

        $a->next();

        $this->assertEquals(4, $a->current());
        $this->assertEquals(3, $b->current());

        $b->next();

        $this->assertEquals(4, $a->current());
        $this->assertEquals(4, $b->current());
    }

    public function testRememberWithDuplicateKeys()
    {
        $collection = LazyCollection::make(function () {
            yield 'key' => 1;
            yield 'key' => 2;
        })->remember();

        $results = $collection->map(function ($value, $key) {
            return [$key, $value];
        })->values()->all();

        $this->assertSame([['key', 1], ['key', 2]], $results);
    }

    public function testTapEach()
    {
        $data = LazyCollection::times(10);

        $tapped = [];

        $data = $data->tapEach(function ($value, $key) use (&$tapped) {
            $tapped[$key] = $value;
        });

        $this->assertEmpty($tapped);

        $data = $data->take(5)->all();

        $this->assertSame([1, 2, 3, 4, 5], $data);
        $this->assertSame([1, 2, 3, 4, 5], $tapped);
    }
}