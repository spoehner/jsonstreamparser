<?php
declare(strict_types=1);
namespace JsonStreamParser\Decoder;

/**
 * @author  Stefan Pöhner <github@poe-php.de>
 * @license MIT
 *
 * @package JsonStreamParser
 */
class Element
{
	const TYPE_VALUE  = 1;
	const TYPE_ARRAY  = 2;
	const TYPE_OBJECT = 3;
	const TYPE_KEY    = 4;

	/**
	 * @var int
	 */
	public $type;

	/**
	 * @var self|null
	 */
	public $parent;

	/**
	 * @var mixed
	 */
	public $value;

	/**
	 * Element constructor.
	 *
	 * @param int $type
	 */
	public function __construct(int $type)
	{
		$this->type = $type;
	}
}
