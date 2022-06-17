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
 * @link        https://lenevor.com
 * @copyright   Copyright (c) 2019 - 2022 Alexander Campo <jalexcam@gmail.com>
 * @license     https://opensource.org/licenses/BSD-3-Clause New BSD license or see https://lenevor.com/license or see /license.md
 */
 
namespace Syscodes\Components\Http;

use JsonSerializable;
use BadMethodCallException;
use InvalidArgumentException;
use UnexpectedValueException;
use Syscodes\Components\Http\Contributors\Server;
use Syscodes\Components\Http\Contributors\Status;
use Syscodes\Components\Http\Concerns\ResponseLoads;
use Syscodes\Components\Contracts\Support\Renderable;
use Syscodes\Components\Http\Response\ResponseHeaders;

/**
 * Response represents an HTTP response.
 * 
 * @author Alexander Campo <jalexcam@gmail.com>
 */
class Response extends Status 
{
	use ResponseLoads;

	/**
	 * Sets up the response with a content and a status code.
	 *
	 * @param  mixed  $content  The response content 
	 * @param  int  $status  The response status  
	 * @param  array  $headers  Array of HTTP headers for this response
	 *
	 * @return string
	 */
	public function __construct($content = '', int $status = 200, array $headers = [])
	{
		$this->setContent($content);
		$this->setStatusCode($status);
		
		$this->server  = new Server($_SERVER);
		$this->headers = new ResponseHeaders($headers);
	}

	/**
	 * Creates an instance of the same response class for rendering contents to the content, 
	 * status code and headers.
	 *
	 * @param  mixed  $content  The response content  
	 * @param  int  $status  The HTTP response status for this response  
	 * @param  array  $headers  Array of HTTP headers for this response
	 *
	 * @return static
	 */
	public static function render($content = '', $status = 200, $headers = [])
	{
		return new static($content, $status, $headers);
	}

	/**
	 * Gets the current response content.
	 * 
	 * @return string
	 */
	public function getContent(): string
	{
		return $this->content;
	}

	/**
	 * Gets the response status code.
	 *
	 * The status code is a 3-digit code to specify server response results to the browser.
	 *
	 * @return int
	 *
	 * @throws \BadMethodCallException
	 */
	public function getStatusCode(): int
	{
		if (empty($this->status)) {
			throw new BadMethodCallException('HTTP Response is missing a status code.');
		}

		return $this->status;
	}

	/**
	 * Sends the headers if they haven't already been sent. 
	 * Returns whether they were sent or not.
	 *
	 * @return bool|self
	 *
	 * @uses   \Syscodes\Components\Http\Http
	 */
	public function sendHeaders()
	{
		// Have the headers already been sent?
		if (headers_sent()) {
			return $this;
		}

		// Headers
		foreach ($this->headers->allPreserveCase() as $name => $values) {
			$replace = 0 === strcasecmp($name, 'Content-Type');

			foreach ($values as $value) {
				header($name.': '. $value, $replace, $this->status);
			}
		}
		
		// Cookies
		// foreach ($this->headers->getCookies() as $cookie) {
		// 	header('Set-Cookie: '.$cookie, false, $this->status);
		// }

		// Status
		if ( ! empty($_SERVER['FCGI_SERVER_VERSION'])) {
			// Send the protocol/status line first, FCGI servers need different status header
			header(sprintf('Status: %s %s', $this->status, $this->statusText));
		} else {
			$this->protocol = (string) $this->server->get('SERVER_PROTOCOL') ?: 'HTTP/1.1';
			header(sprintf('%s %s %s', $this->protocol, $this->status, $this->statusText), true, $this->status);
		}

		return $this;
	}

	/**
	 * Sends content for the current web response.
	 * 
	 * @return self
	 */
	public function sendContent(): self
	{
		echo $this->content;

		return $this;
	}

	/**
	 * Sends the response to the output buffer. Optionally, headers will be sent. 
	 *
	 * @param  bool  $sendHeader  Whether or not to send the defined HTTP headers
	 *
	 * @return  self
	 */
	public function send($sendHeader = false): self
	{
		if ($sendHeader) {
			$this->sendHeaders();
		}

		if (null !== $this->content) {
			$this->sendContent();
		}

		return $this;
	}

	/**
	 * Sends the content of the message to the browser.
	 *
	 * @param  mixed  $content  The response content
	 *
	 * @return self
	 */
	public function setContent($content): self
	{
		if ($content !== null && ! is_string($content) && ! is_numeric($content) && ! is_bool($content) && ! is_callable([$content, '__toString'])) {
			throw new UnexpectedValueException(sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', gettype($content)));
		}

		if ($content instanceof JsonSerializable || is_array($content)) {
			$this->header('Content-Type', 'application/json');

			$content = json_encode($content);
		} elseif ($content instanceof Renderable) {
			$content = $content->render();
		}
		
		$this->content = $content ?? '';

		return $this;
	}

	/**
	 * Prepares the Response before it is sent to the client.
	 * 
	 * @param  \Syscodes\Components\Http\Request  $request
	 * 
	 * @return self
	 */
	public function prepare($request): self
	{
		$headers = $this->headers;

		if ($this->isInformational() || $this->isEmpty()) {
			$this->setContent(null);
			$headers->remove('Content-Type');
			$headers->remove('Content-Length');
		}

		return $this;
	}

	/**
	* Sets the response status code.
	*
	* @param  int  $code  The status code
	* @param  string|null  $text  The status text
	*
	* @return self
	*
	* @throws \InvalidArgumentException
	*/
	public function setStatusCode(int $code, $text = null): self
	{
		$this->status = $code; 

		// Valid range?
		if ($this->isInvalid()) {
			throw new InvalidArgumentException(__('response.statusCodeNotValid', ['code' => $code]));			
		}

		// Check if you have an accepted status code if not shows to a message of unknown status
		if (null === $text) {
			$this->statusText = isset($this->statusCodes[$code]) ? $this->statusCodes[$code] : __('response.UnknownStatus');

			return $this;
		}

		if (false === $text) {
			$this->statusText = '';

			return $this;
		}

		$this->statusText = $text;

		return $this;
	}

	/**
	 * Is response invalid?
	 * 
	 * @final
	 * 
	 * @return bool
	 */
	public function isInvalid(): bool
	{
		return $this->status < 100 || $this->status >= 600;
	}

	/**
	 * Is response informative?
	 * 
	 * @final
	 * 
	 * @return bool
	 */
	public function isInformational(): bool
	{
		return $this->status >= 100 && $this->status < 200;
	}
	
	/**
	 * Is the response a redirect?
	 * 
	 * @final
	 * 
	 * @return void
	 */
	public function isRedirection(): bool
	{
		return $this->status >= 300 && $this->status < 400;
	}
	
	/**
	 * Is the response empty?
	 * 
	 * @final
	 * 
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return in_array($this->status, [204, 304]);
	}
	
	/**
	 * Is the response a redirect of some form?
	 * 
	 * @return bool
	 */
	public function isRedirect(): bool
	{
		return in_array($this->status, [301, 302, 303, 307, 308]);
	}
	
	/**
	 * Magic method.
	 * 
	 * Returns the Response as an HTTP string.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return sprintf('%s %s %s', $this->protocol, $this->status, $this->statusText)."\r\n".
			$this->headers."\r\n".
			$this->getContent();
	}
	
	/**
	 * Magic method.
	 * 
	 * Clone the current Response instance.
	 * 
	 * @return void
	 */
	public function __clone()
	{
		$this->headers = clone $this->headers;
	}
}