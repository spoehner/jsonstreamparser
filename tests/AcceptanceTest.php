<?php
declare(strict_types=1);
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
class AcceptanceTest extends TestCase
{
	public function testEscapedChars()
	{
		$fileName = __DIR__.'/data/example.json';
		$stream   = fopen($fileName, 'rb');
		$config   = new Configuration();
		$decoder  = new Decoder();

		$parser = new JsonStreamParser($config, $decoder);
		$parser->parse($stream);
		fclose($stream);

		$result = $decoder->getResult();
		$this->assertEquals(json_decode(file_get_contents($fileName)), $result);
	}
}
