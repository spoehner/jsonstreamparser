<?php
namespace Test;

use JsonStreamParser\Configuration;
use JsonStreamParser\JsonStreamParser;
use PHPUnit\Framework\TestCase;

/**
 * @author Stefan Pöhner <github@poe-php.de>
 * @license MIT
 *
 * @package JsonStreamParser
 */
class JsonStreamParserTest extends TestCase
{
	/**
	 * @var resource
	 */
	protected $stream;

	/**
	 * @var Configuration
	 */
	protected $configuration;

	protected function setUp()
	{
		parent::setUp();

		$this->stream = fopen('php://temp', 'r+');
		$this->configuration = new Configuration();
	}

	protected function tearDown()
	{
		parent::tearDown();

		fclose($this->stream);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage no resource
	 */
	public function testParseNoResourceGiven()
	{
		$subject = new JsonStreamParser($this->configuration);
		$subject->parse(null);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Missing buffer class
	 */
	public function testParseMissingBufferClass()
	{
		$this->configuration->bufferClass = 'DoesNotExistClass';

		$subject = new JsonStreamParser($this->configuration);
		$subject->parse($this->stream);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Incompatible buffer class
	 */
	public function testParseIncompatibleBufferClass()
	{
		$this->configuration->bufferClass = self::class;

		$subject = new JsonStreamParser($this->configuration);
		$subject->parse($this->stream);
	}

	public function testParseEmptyStream()
	{
		fprintf($this->stream, '');

		$subject = new JsonStreamParser($this->configuration);
		$subject->parse($this->stream);
	}

	public function testParsePrimitive()
	{
		fprintf($this->stream, '{}');

		$subject = new JsonStreamParser($this->configuration);
		$subject->parse($this->stream);
	}

	public function testParse()
	{
		$subject = new JsonStreamParser($this->configuration);
		$this->assertNull($subject->parse($this->stream));
	}
}
