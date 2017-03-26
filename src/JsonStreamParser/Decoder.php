<?php
declare(strict_types=1);
namespace JsonStreamParser;

use JsonStreamParser\Decoder\Element;

/**
 * @author  Stefan Pöhner <github@poe-php.de>
 * @license MIT
 *
 * @package JsonStreamParser
 */
class Decoder
{
	/**
	 * @var mixed
	 */
	private $result;

	/**
	 * @var Element
	 */
	private $current;

	public function endOfStream()
	{
	}

	public function beginObject()
	{
		$this->createChild(Element::TYPE_OBJECT, new \stdClass());
	}

	public function endObject()
	{
		if ($this->current->type != Element::TYPE_OBJECT) {
			throw new \UnexpectedValueException('There is no object to end.');
		}

		$this->moveUp();
	}

	public function beginArray()
	{
		$this->createChild(Element::TYPE_ARRAY, []);
	}

	public function endArray()
	{
		if ($this->current->type != Element::TYPE_ARRAY) {
			throw new \UnexpectedValueException('There is no array to end.');
		}

		$this->moveUp();
	}

	/**
	 * @param mixed $value
	 */
	public function appendValue($value)
	{
		if ($this->current instanceof Element) {
			if ($this->current->type == Element::TYPE_ARRAY) {
				$this->current->value[] = $value;
			} elseif ($this->current->type == Element::TYPE_OBJECT) {
				$this->createChild(Element::TYPE_KEY, $value);
			} elseif ($this->current->type == Element::TYPE_KEY) {
				// get the key back
				$key = $this->current->value;
				// move up to object
				$this->current = $this->current->parent;
				// append key value pair
				$this->current->value->$key = $value;
			}
		} else {
			$this->result = $value;
		}
	}

	public function whitespace($char)
	{
	}

	public function keyValueSeparator()
	{
		if (!$this->current instanceof Element || $this->current->type != Element::TYPE_KEY) {
			throw new \UnexpectedValueException('Not in object context.');
		}
	}

	public function arraySeparator()
	{
		if (!$this->current instanceof Element || !in_array($this->current->type, [Element::TYPE_ARRAY, Element::TYPE_OBJECT])) {
			throw new \UnexpectedValueException('Not in array or object context.');
		}
	}

	/**
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * Move up the tree.
	 */
	private function moveUp()
	{
		$value = $this->current->value;
		if ($this->current->parent) {
			$this->current = $this->current->parent;
		} else {
			$this->current = null;
		}

		$this->appendValue($value);
	}

	/**
	 * @param int   $type
	 * @param mixed $value
	 */
	private function createChild(int $type, $value)
	{
		$element         = new Element($type);
		$element->parent = $this->current;
		$element->value  = $value;
		$this->current   = $element;
	}
}
