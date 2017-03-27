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
	 * @var \Generator
	 */
	private $generator;

	/**
	 * @var string
	 */
	private $currentChar;

	/**
	 * @var int
	 */
	private $currentNestingLevel = 0;

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
		$this->currentNestingLevel = 0;

		$this->generator = $this->buffer->get();
		foreach ($this->generator as $char) {
			$this->currentChar = $char;
			$this->processChar();
		}

		if ($this->currentNestingLevel != 0) {
			throw new ParseException('Unexpected end of stream');
		}

		$this->decoder->endOfStream();
	}

	private function processChar()
	{
		switch ($this->currentChar) {
			case JsonDefinition::BEGIN_OBJECT:
				$this->currentNestingLevel++;
				$this->decoder->beginObject();
			break;

			case JsonDefinition::END_OBJECT:
				$this->currentNestingLevel--;
				$this->decoder->endObject();
			break;

			case JsonDefinition::BEGIN_ARRAY:
				$this->currentNestingLevel++;
				$this->decoder->beginArray();
			break;

			case JsonDefinition::END_ARRAY:
				$this->currentNestingLevel--;
				$this->decoder->endArray();
			break;

			case JsonDefinition::ARRAY_SEPARATOR:
				$this->decoder->arraySeparator();
			break;

			case JsonDefinition::KEY_VALUE_SEPARATOR:
				$this->decoder->keyValueSeparator();
			break;

			case JsonDefinition::STRING_ENCLOSURE:
				$string = $this->consumeString();
				$this->decoder->appendValue($string);
			break;

			default:
				if ($this->isWhitespace()) {
					$this->decoder->whitespace($this->currentChar);
				} elseif ($this->isStartOfKeyword($this->currentChar)) {
					$value = $this->consumeKeyword();
					$this->decoder->appendValue($value);
				} elseif (is_numeric($this->currentChar)) {
					$value = $this->consumeNumber();
					$this->decoder->appendValue($value);

					// if the generator has not closed behind the number,
					// consumeNumber walks one character too far.
					// this character needs to be processed
					if ($this->generator->valid()) {
						$this->processChar();
					}
				} else {
					throw new ParseException("Unknown character: {$this->currentChar}");
				}
			break;
		}
	}

	/**
	 * @return string
	 * @throws ParseException
	 */
	private function consumeString(): string
	{
		$string = '';

		// the cursor is at the opening enclosure, so advance
		$this->generator->next();
		while ($this->generator->valid()) {
			$char = $this->generator->current();

			// read until we reach another enclosure
			if ($char === JsonDefinition::STRING_ENCLOSURE) {
				$decodedString = json_decode('"'.$string.'"');
				return $decodedString;
			}

			$string .= $char;

			// keep this after the return; otherwise the foreach of doParse will skip one char
			$this->generator->next();
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
	 * @return bool|null
	 * @throws ParseException
	 */
	private function consumeKeyword()
	{
		$keyword = '';

		// cursor is already on the first character
		do {
			$keyword .= mb_strtolower($this->generator->current());

			if (array_key_exists($keyword, JsonDefinition::KEYWORDS)) {
				return JsonDefinition::KEYWORDS[$keyword];
			}

			$this->generator->next();
		} while ($this->generator->valid());

		// there was a typo
		throw new ParseException('Encountered end of stream while inside a keyword.');
	}

	/**
	 * @return float|int
	 * @throws ParseException
	 */
	private function consumeNumber()
	{
		$number           = '';
		$isInt            = true;
		$numberCharacters = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', 'e'];

		// cursor is already on the first character
		do {
			$this->currentChar = mb_strtolower($this->generator->current());
			if (!in_array($this->currentChar, $numberCharacters)) {
				// number has ended, see if it really was a number
				if (!is_numeric($number)) {
					throw new ParseException("Unknown number format: $number");
				}
				break;
			} elseif ($this->currentChar == '.') {
				$isInt = false;
			}

			$number .= $this->currentChar;
			$this->generator->next();
		} while ($this->generator->valid());

		if ($isInt) {
			return (int)$number;
		}

		return (float)$number;
	}

	/**
	 * @return bool
	 */
	private function isWhitespace(): bool
	{
		return in_array($this->currentChar, JsonDefinition::WHITESPACE);
	}
}
