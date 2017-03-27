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

	/**
	 * @expectedException \JsonStreamParser\Exception\ParseException
	 * @expectedExceptionMessage Unknown character
	 */
	public function testParseErrorUnknownCharacter()
	{
		$this->fillStream('a');

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
	 * @param string $input
	 *
	 * @expectedException \JsonStreamParser\Exception\ParseException
	 * @dataProvider errorProvider
	 */
	public function testParseError(string $input)
	{
		$this->fillStream($input);

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	public function errorProvider()
	{
		return [['{']];
	}

	public function testParsePrimitiveObject()
	{
		$this->fillStream('{}');

		$this->decoder->expects($this->once())->method('beginObject');
		$this->decoder->expects($this->once())->method('endObject');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	public function testParseObject()
	{
		$this->fillStream('{"key":"value"}');

		$this->decoder->expects($this->once())->method('beginObject');
		$this->decoder->expects($this->at(1))->method('appendValue')->with('key');
		$this->decoder->expects($this->once())->method('keyValueSeparator');
		$this->decoder->expects($this->at(3))->method('appendValue')->with('value');
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

	public function testParseArray()
	{
		$this->fillStream('["foobar"]');

		$this->decoder->expects($this->once())->method('beginArray');
		$this->decoder->expects($this->once())->method('appendValue')->with('foobar');
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
		$this->decoder->expects($this->once())->method('arraySeparator');

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

	/**
	 * @expectedException \JsonStreamParser\Exception\ParseException
	 * @expectedExceptionMessageRegExp  /end of stream.* keyword/
	 */
	public function testParseErrorKeywordTypo()
	{
		$this->fillStream('ture');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	/**
	 * @param mixed $input
	 *
	 * @dataProvider scalarProvider
	 */
	public function testParseScalar($input)
	{
		$this->fillStream(json_encode($input));
		$this->decoder->expects($this->once())->method('appendValue')->with($input);

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	public function scalarProvider()
	{
		return [[1], [123], [1.23], [1.3e10], [-12], [1.2e-3], [-1e-2], [true], [false], [null]];
	}

	public function testParseArrayOfNumbers()
	{
		$this->fillStream(json_encode([123, 456, 1.23, -1]));
		$this->decoder->expects($this->once())->method('beginArray');
		$this->decoder->expects($this->at(1))->method('appendValue')->with(123);
		$this->decoder->expects($this->at(2))->method('arraySeparator');
		$this->decoder->expects($this->at(3))->method('appendValue')->with(456);
		$this->decoder->expects($this->at(4))->method('arraySeparator');
		$this->decoder->expects($this->at(5))->method('appendValue')->with(1.23);
		$this->decoder->expects($this->once())->method('endArray');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	/**
	 * @expectedException \JsonStreamParser\Exception\ParseException
	 * @expectedExceptionMessage number format
	 */
	public function testParseNumberError()
	{
		$this->fillStream('1ea23');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	/**
	 * @param string $string
	 *
	 * @dataProvider specialProvider
	 */
	public function testParseSpecialCharacters(string $string)
	{
		$this->fillStream('"'.$string.'"');
		$this->decoder->expects($this->once())->method('appendValue')->with(json_decode('"'.$string.'"'));

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}

	public function specialProvider()
	{
		return [['üâé'], ['ú™£¢∞§\u2665']];
	}

	public function testParse()
	{
		$this->decoder->expects($this->once())->method('endOfStream');

		$subject = new JsonStreamParser($this->configuration, $this->decoder);
		$subject->parse($this->stream);
	}
}
