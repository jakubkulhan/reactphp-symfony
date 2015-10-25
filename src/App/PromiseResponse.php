<?php
namespace App;

use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 */
class PromiseResponse extends Response implements PromiseInterface
{

	/** @var PromiseInterface */
	private $wrappedPromise;

	public function __construct(PromiseInterface $wrappedPromise)
	{
		$this->wrappedPromise = $wrappedPromise;
	}

	public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
	{
		return $this->wrappedPromise->then($onFulfilled, $onRejected, $onProgress);
	}

	public static function wrapPromise(GetResponseForControllerResultEvent $event)
	{
		if (!$event->hasResponse() && $event->getControllerResult() instanceof PromiseInterface) {
			$event->setResponse(new self($event->getControllerResult()));
		}
	}

}
