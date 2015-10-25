<?php
namespace App\Controller;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use Skrz\Bundle\AutowiringBundle\Annotation\Autowired;
use Skrz\Bundle\AutowiringBundle\Annotation\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 *
 * @Controller
 */
class IndexController
{

	/**
	 * @var LoopInterface
	 *
	 * @Autowired
	 */
	public $loop;

	public function indexAction(Request $request)
	{
		return Response::create("Hello, world!\n");
	}

	public function promiseAction(Request $request)
	{
		$secs = intval($request->attributes->get("secs"));

		$deferred = new Deferred();

		$this->loop->addTimer($secs, function () use ($secs, $deferred) {
			$deferred->resolve(Response::create("{$secs} seconds later...\n"));
		});

		return $deferred->promise();
	}

}
