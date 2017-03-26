<?php
declare(strict_types=1);
namespace JsonStreamParser;

/**
 * @author  Stefan Pöhner <github@poe-php.de>
 * @license MIT
 *
 * @package JsonStreamParser
 */
abstract class JsonDefinition
{
	const WHITESPACE       = [" ", "\t", "\n", "\r"];
	const BEGIN_OBJECT     = '{';
	const END_OBJECT       = '}';
	const BEGIN_ARRAY      = '[';
	const END_ARRAY        = ']';
	const STRING_ENCLOSURE = '"';
	const ARRAY_SEPARATOR  = ',';
}
