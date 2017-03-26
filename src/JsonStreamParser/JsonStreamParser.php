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
	const STATE_NOTHING   = 1;
	const STATE_IN_ARRAY  = 2;
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
		$nestingLevel = 0;

		$generator = $this->buffer->get();
		foreach ($generator as $char) {
			switch ($char) {
				case JsonDefinition::BEGIN_OBJECT:
					$nestingLevel++;
					$this->decoder->beginObject();
				break;

				case JsonDefinition::END_OBJECT:
					$nestingLevel--;
					$this->decoder->endObject();
				break;

				case JsonDefinition::BEGIN_ARRAY:
					$nestingLevel++;
					$this->decoder->beginArray();
				break;

				case JsonDefinition::END_ARRAY:
					$nestingLevel--;
					$this->decoder->endArray();
				break;

				case JsonDefinition::ARRAY_SEPARATOR:
					$this->decoder->arraySeparator();
				break;

				case JsonDefinition::KEY_VALUE_SEPARATOR:
					$this->decoder->keyValueSeparator();
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

					if ($this->isStartOfKeyword($char)) {
						$value = $this->consumeKeyword($generator);
						$this->decoder->appendValue($value);
						continue;
					}

					if (is_numeric($char)) {
						$value = $this->consumeNumber($generator);
						$this->decoder->appendValue($value);
						continue;
					}

					throw new ParseException("Unknown character: $char");
			}
		}

		if ($nestingLevel != 0) {
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

			// read until we reach another enclosure
			if ($char === JsonDefinition::STRING_ENCLOSURE) {
				return $string;
			}

			$string .= $char;

			// keep this after the return; otherwise the foreach of doParse will skip one char
			$generator->next();
		}

		// if we end up here, we never got an enclosure
		throw new ParseException('Encountered end of stream while inside a string.');
	}

	/**
	 * @param string $char
	 *
	 * @return bool
	 */
	private function isStartOfKeyword(string $char): bool
	{
		// true, false, null
		return in_array(mb_strtolower($char), ['t', 'f', 'n']);
	}

	/**
	 * @param \Generator $generator
	 *
	 * @return bool|null
	 * @throws ParseException
	 */
	private function consumeKeyword(\Generator $generator)
	{
		$keyword = '';

		// cursor is already on the first character
		do {
			$keyword .= mb_strtolower($generator->current());

			if (array_key_exists($keyword, JsonDefinition::KEYWORDS)) {
				return JsonDefinition::KEYWORDS[$keyword];
			}

			$generator->next();
		} while ($generator->valid());

		// there was a typo
		throw new ParseException('Encountered end of stream while inside a keyword.');
	}

	/**
	 * @param \Generator $generator
	 *
	 * @return float|int
	 * @throws ParseException
	 */
	private function consumeNumber(\Generator $generator)
	{
		$number = '';
		$isInt  = true;

		// cursor is already on the first character
		do {
			$number .= mb_strtolower($generator->current());
			$generator->next();
		} while ($generator->valid());

		if ($isInt) {
			return (int)$number;
		}

		return (float)$number;
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
