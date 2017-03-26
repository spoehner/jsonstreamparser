<?php
declare(strict_types=1);

namespace JsonStreamParser;

/**
 * @author  Stefan Pöhner <github@poe-php.de>
 * @license MIT
 *
 * @package JsonStreamParser
 */
class JsonStreamParser
{
	/**
	 * @var Configuration
	 */
	private $config;

	/**
	 * @var Buffer
	 */
	private $buffer;

	/**
	 * JsonStreamParser constructor.
	 *
	 * @param Configuration $config
	 */
	public function __construct(Configuration $config)
	{
		$this->config = $config;
	}

	/**
	 * Parse a JSON stream.
	 * This is the entry point to the whole system.
	 *
	 * @param resource $stream
	 *
	 * @return void
	 */
	public function parse($stream)
	{
		if (!is_resource($stream)) {
			throw new \InvalidArgumentException('The stream provided is no resource.');
		}

		if (!class_exists($this->config->bufferClass)) {
			throw new \InvalidArgumentException('Missing buffer class.');
		}

		$cn = $this->config->bufferClass;
		$this->buffer = new $cn();
		if (!$this->buffer instanceof Buffer) {
			throw new \InvalidArgumentException('Incompatible buffer class.');
		}
	}
}
