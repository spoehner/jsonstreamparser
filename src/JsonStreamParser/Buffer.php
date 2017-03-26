<?php
declare(strict_types=1);
namespace JsonStreamParser;

/**
 * @author  Stefan Pöhner <github@poe-php.de>
 * @license MIT
 *
 * @package JsonStreamParser
 */
class Buffer
{
	/**
	 * @var resource
	 */
	private $stream;

	/**
	 * @var int
	 */
	private $size;

	/**
	 * @param resource $stream
	 */
	public function setStream($stream)
	{
		$this->stream = $stream;
	}

	/**
	 * @param int $size
	 */
	public function setSize(int $size)
	{
		$this->size = $size;
	}

	/**
	 * Get the next character and advance the cursor.
	 *
	 * @return \Generator
	 */
	public function get(): \Generator
	{
		while (!feof($this->stream)) {
			$chunk  = fread($this->stream, $this->size);
			$length = mb_strlen($chunk);

			for ($i = 0; $i < $length; $i++) {
				yield $chunk[$i];
			}
		}
	}
}
