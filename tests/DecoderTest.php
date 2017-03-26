<?php
declare(strict_types=1);
namespace Test;

use JsonStreamParser\Decoder;
use PHPUnit\Framework\TestCase;

/**
 * @author  Stefan Pöhner <github@poe-php.de>
 * @license MIT
 *
 * @package JsonStreamParser
 */
class DecoderTest extends TestCase
{
	public function testEmpty()
	{
		$subject = new Decoder();
		$this->assertNull($subject->getResult());
	}

	public function testString()
	{
		$subject = new Decoder();
		$subject->appendValue('foobar');
		$this->assertSame('foobar', $subject->getResult());
	}

	public function testSimpleObject()
	{
		$subject = new Decoder();
		$subject->beginObject();
		$subject->endObject();
		$this->assertEquals(new \stdClass(), $subject->getResult());
	}

	public function testSimpleArray()
	{
		$subject = new Decoder();
		$subject->beginArray();
		$subject->endArray();
		$this->assertSame([], $subject->getResult());
	}

	public function testSimpleNestedArrays()
	{
		$subject = new Decoder();
		$subject->beginArray();
		$subject->beginArray();
		$subject->endArray();
		$subject->endArray();
		$this->assertSame([[]], $subject->getResult());
	}

	public function testArray()
	{
		$subject = new Decoder();
		$subject->beginArray();
		$subject->appendValue('one');
		$subject->beginArray();
		$subject->appendValue('inner');
		$subject->endArray();
		$subject->appendValue('two');
		$subject->endArray();
		$this->assertSame(['one', ['inner'], 'two'], $subject->getResult());
	}

	public function testObject()
	{
		$subject = new Decoder();
		$subject->beginObject();
		$subject->appendValue('key1');
		$subject->appendValue('value1');
		$subject->appendValue('key2');
		$subject->appendValue('value2');
		$subject->endObject();
		$this->assertEquals((object)['key1' => 'value1', 'key2' => 'value2'], $subject->getResult());
	}

	/**
	 * @expectedException \UnexpectedValueException
	 * @expectedExceptionMessage object
	 */
	public function testUnexpectedKeyValueSeparator()
	{
		$subject = new Decoder();
		$subject->beginObject();
		$subject->keyValueSeparator();
	}
}
