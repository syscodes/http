<?php

/**
 * Lenevor Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file license.md.
 * It is also available through the world-wide-web at this URL:
 * https://lenevor.com/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@Lenevor.com so we can send you a copy immediately.
 *
 * @package     Lenevor
 * @subpackage  Base
 * @author      Javier Alexander Campo M. <jalexcam@gmail.com>
 * @link        https://lenevor.com 
 * @copyright   Copyright (c) 2019-2020 Lenevor PHP Framework 
 * @license     https://lenevor.com/license or see /license.md or see https://opensource.org/licenses/BSD-3-Clause New BSD license
 * @since       0.2.0
 */

namespace Syscodes\Http\Contributors;

use Countable;
use IteratorAggregate;
use Syscodes\Support\Arr;

/**
 * Parameters is a container for key/value pairs.
 * 
 * @author Javier Alexander Campo M. <jalexcam@gmail.com>
 */
class Parameters implements IteratorAggregate, Countable
{
	/**
	 * Array parameters from the Server global.
	 *
	 * @var array $parameters
	 */
	protected $parameters = [];

	/**
	 * Parameter Object Constructor.
	 *
	 * @param  array  $parameters
	 *
	 * @return array
	 */
	public function __construct(array $parameters = [])
	{
		$this->parameters = $parameters;
	}

	/**
	 * Returns the parameters.
	 * 
	 * @return array
	 */
	public function all()
	{
		return $this->parameters;
	}

	/**
	 * Get a parameter array item.
	 *
	 * @param  string  $key
	 * @param  string|null  $fallback 
	 *
	 * @return mixed
	 */
	public function get($key, $fallback = null)
	{
		if (Arr::exists($this->parameters, $key))
		{
			return $this->parameters[$key];
		}

		return $fallback;
	}

	/**
	 * Check if a parameter array item exists.
	 *
	 * @param  string  $key
	 *
	 * @return mixed
	 */
	public function has($key)
	{
		return Arr::exists($this->parameters, $key);
	}

	/**
	 * Set a parameter array item.
	 *
	 * @param  string  $key
	 * @param  string  $value 
	 *
	 * @return mixed
	 */
	public function set($key, $value)
	{
		$this->parameters[$key] = $value;
	}

	/**
	 * Remove a parameter array item.
	 *
	 * @param  string  $key 
	 *
	 * @return void
	 */
	public function remove($key)
	{
		if ($this->has($key))
		{
			unset($this->parameters[$key]);
		}
	}

	/*
	|-----------------------------------------------------------------
	| IteratorAggregate Method
	|-----------------------------------------------------------------
	*/
	
	/**
	 * Retrieve an external iterator.
	 * 
	 * @see    \IteratorAggregate::getIterator
	 * 
	 * @return new \ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->frames);
	}
	
	/*
	|-----------------------------------------------------------------
	| Countable Method
	|-----------------------------------------------------------------
	*/
	
	/**
	 * Returns the number of parameters.
	 * 
	 * @return int The number of parameters
	 */
	public function count()
	{
		return count($this->parameters);
	}
}