<?php
namespace Test;

use JsonStreamParser\Configuration;
use JsonStreamParser\Decoder;
use JsonStreamParser\JsonStreamParser;
use PHPUnit\Framework\TestCase;

/**
 * @author  Stefan Pöhner <github@poe-php.de>
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

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|Decoder
	 */
	protected $decoder;

	protected function setUp()
	{
		parent::setUp();

		$this->stream        = fopen('php://temp', 'r+');
		$this->configuration = new Configuration();
		$this->decoder       = $this->createMock(Decoder::class);
	}

	protected function tearDown()
	{
		parent::tearDown();

		fclose($this->stream);
	}

	/**
	 * @param string $content
	 */
	protected function fillStream(string $content)
	{
		fprintf($this->stream, $content);
		rewind($this->stream);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage no resource
	 */
	public function testParseNoResourceGiven()
	{
		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse(null);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Missing buffer class
	 */
	public function testParseMissingBufferClass()
	{
		$this->configuration->bufferClass = 'DoesNotExistClass';

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Incompatible buffer class
	 */
	public function testParseIncompatibleBufferClass()
	{
		$this->configuration->bufferClass = self::class;

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	public function testParseEmptyStream()
	{
		$this->fillStream('');

		$this->decoder->expects($this->once())->method('endOfStream');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	/**
	 * @expectedException \JsonStreamParser\Exception\ParseException
	 * @expectedExceptionMessage end of stream
	 */
	public function testParseError()
	{
		$this->fillStream('{');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	public function testParsePrimitiveObject()
	{
		$this->fillStream('{}');

		$this->decoder->expects($this->once())->method('beginObject');
		$this->decoder->expects($this->once())->method('endObject');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	public function testParsePrimitiveArray()
	{
		$this->fillStream('[]');

		$this->decoder->expects($this->once())->method('beginArray');
		$this->decoder->expects($this->once())->method('endArray');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	public function testParseArrayOfEmptyObjects()
	{
		$this->fillStream('[{}, {}]');

		$this->decoder->expects($this->once())->method('beginArray');
		$this->decoder->expects($this->once())->method('endArray');
		$this->decoder->expects($this->exactly(2))->method('beginObject');
		$this->decoder->expects($this->exactly(2))->method('endObject');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	/**
	 * @param string $char
	 *
	 * @dataProvider whitespaceProvider
	 */
	public function testParseWhitespace(string $char)
	{
		$this->fillStream('{'.$char.'}');

		$this->decoder->expects($this->once())->method('whitespace')->with($char);

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	public function whitespaceProvider()
	{
		return [[" "], ["\t"], ["\n"], ["\r"]];
	}

	public function testParseString()
	{
		$string = 'foobar';
		$this->fillStream('"'.$string.'"');

		$this->decoder->expects($this->once())->method('appendValue')->with($string);

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	/**
	 * @expectedException \JsonStreamParser\Exception\ParseException
	 * @expectedExceptionMessageRegExp  /end of stream.* string/
	 */
	public function testParseErrorMissingStringEnd()
	{
		$this->fillStream('"foobar');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	public function testParse()
	{
		$this->decoder->expects($this->once())->method('endOfStream');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}
}
