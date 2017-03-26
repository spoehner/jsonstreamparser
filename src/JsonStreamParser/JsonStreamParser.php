<?php
declare(strict_types=1);

namespace JsonStreamParser;

use JsonStreamParser\Exception\ParseException;

/**
 * @author  Stefan Pöhner <github@poe-php.de>
 * @license MIT
 *
 * @package JsonStreamParser
 */
class JsonStreamParser
{
	const STATE_NOTHING  = 1;
	const STATE_IN_ARRAY = 2;
	const STATE_IN_OBJECT = 3;

	/**
	 * @var Configuration
	 */
	private $config;

	/**
	 * @var Buffer
	 */
	private $buffer;

	/**
	 * @var Decoder
	 */
	private $decoder;

	/**
	 * JsonStreamParser constructor.
	 *
	 * @param Configuration $config
	 * @param Decoder       $decoder
	 */
	public function __construct(Configuration $config, Decoder $decoder)
	{
		$this->config  = $config;
		$this->decoder = $decoder;
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

		$cn           = $this->config->bufferClass;
		$this->buffer = new $cn();
		if (!$this->buffer instanceof Buffer) {
			throw new \InvalidArgumentException('Incompatible buffer class.');
		}

		$this->buffer->setStream($stream);
		$this->buffer->setSize($this->config->bufferSize);

		$this->doParse();
	}

	private function doParse()
	{
		$state = self::STATE_NOTHING;

		$generator = $this->buffer->get();
		foreach ($generator as $char) {
			switch ($char) {
				case JsonDefinition::BEGIN_OBJECT:
					$state = self::STATE_IN_OBJECT;
					$this->decoder->beginObject();
				break;

				case JsonDefinition::END_OBJECT:
					$state = self::STATE_NOTHING;
					$this->decoder->endObject();
				break;

				case JsonDefinition::BEGIN_ARRAY:
					$state = self::STATE_IN_ARRAY;
					$this->decoder->beginArray();
				break;

				case JsonDefinition::END_ARRAY:
					$state = self::STATE_NOTHING;
					$this->decoder->endArray();
				break;

				case JsonDefinition::ARRAY_SEPARATOR:
				break;

				case JsonDefinition::STRING_ENCLOSURE:
					$string = $this->consumeString($generator);
					$this->decoder->appendValue($string);
				break;

				default:
					if ($this->isWhitespace($char)) {
						$this->decoder->whitespace($char);
						continue;
					}

					throw new ParseException("Unknown character: $char");
			}
		}

		if ($state != self::STATE_NOTHING) {
			throw new ParseException('Unexpected end of stream');
		}

		$this->decoder->endOfStream();
	}

	/**
	 * @param \Generator $generator
	 *
	 * @return string
	 * @throws ParseException
	 */
	private function consumeString(\Generator $generator): string
	{
		$string = '';

		// the cursor is at the opening enclosure, so advance
		$generator->next();
		while ($generator->valid()) {
			$char = $generator->current();
			$generator->next();

			// read until we reach another enclosure
			if ($char === JsonDefinition::STRING_ENCLOSURE) {
				return $string;
			}

			$string .= $char;
		}

		// if we end up here, we never got an enclosure
		throw new ParseException('Encountered end of stream while inside a string.');
	}

	/**
	 * @param string $char
	 *
	 * @return bool
	 */
	private function isWhitespace(string $char): bool
	{
		return in_array($char, JsonDefinition::WHITESPACE);
	}
}
