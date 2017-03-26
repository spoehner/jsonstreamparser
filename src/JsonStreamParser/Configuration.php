<?php
declare(strict_types=1);
namespace JsonStreamParser;

/**
 * @author  Stefan Pöhner <github@poe-php.de>
 * @license MIT
 *
 * @package JsonStreamParser
 */
class Configuration
{
	/**
	 * Define the buffer size for reading from the stream.
	 *
	 * @var int
	 */
	public $bufferSize = 4096;

	/**
	 * Define the class, that reads from the stream.
	 * You can inject your own class, just make sure it extends the original class.
	 *
	 * @var string
	 */
	public $bufferClass = Buffer::class;
}
