<?php
namespace App\Command;

use App\AppKernel;
use React\EventLoop\Factory;
use React\Http\Request;
use React\Http\Response;
use React\Http\Server;
use React\Promise\PromiseInterface;
use React\Socket\Server as Socket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 */
class RunCommand extends Command
{

	protected function configure()
	{
		$this
			->setName("run")
			->setDescription("Run app server")
			->addOption("host", null, InputOption::VALUE_REQUIRED, "Host to bind HTTP server to.", "127.0.0.1")
			->addOption("port", null, InputOption::VALUE_REQUIRED, "Port to bind HTTP server to.", 8080)
			->addOption("environment", "e", InputOption::VALUE_REQUIRED, "App server kernel environment.", "dev");
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$kernel = new AppKernel($environment = $input->getOption("environment"), $environment !== "prod");
		$kernel->boot();

		$loop = Factory::create();

		/** @var Container $container */
		$container = $kernel->getContainer();
		$container->set("react.loop", $loop);

		$socket = new Socket($loop);
		$http = new Server($socket);

		$http->on("request", function (Request $request, Response $response) use ($kernel, $loop) {
			$headers = $request->getHeaders();
			$cookies = [];

			if (isset($headers["Cookie"])) {
				foreach ((array)$headers["Cookie"] as $cookieHeader) {
					foreach (explode(";", $cookieHeader) as $cookie) {
						list($name, $value) = explode("=", trim($cookie), 2);
						$cookies[$name] = urldecode($value);
					}
				}
			}

			$symfonyRequest = new SymfonyRequest(
				$request->getQuery(),
				[], // TODO: handle post data
				[],
				$cookies,
				[],
				[
					"REQUEST_URI" => $request->getPath(),
					"SERVER_NAME" => explode(":", $headers["Host"])[0],
					"REMOTE_ADDR" => $request->remoteAddress,
					"QUERY_STRING" => http_build_query($request->getQuery()),
				],
				null // TODO: handle post data
			);

			$symfonyRequest->headers->replace($headers);

			$symfonyResponse = $kernel->handle($symfonyRequest);

			if ($kernel instanceof TerminableInterface) {
				$kernel->terminate($symfonyRequest, $symfonyResponse);
			}

			if ($symfonyResponse instanceof PromiseInterface) {
				$symfonyResponse->then(function (SymfonyResponse $symfonyResponse) use ($response) {
					$this->send($response, $symfonyResponse);

				}, function ($error) use ($loop, $response) {
					echo "Exception: ", (string) $error, "\n";

					$response->writeHead(500, ["Content-Type" => "text/plain"]);
					$response->end("500 Internal Server Error");
					$loop->stop();
				});

			} elseif ($symfonyResponse instanceof SymfonyResponse) {
				$this->send($response, $symfonyResponse);

			} else {
				echo "Unsupported response type: ", get_class($symfonyResponse), "\n";

				$response->writeHead(500, ["Content-Type" => "text/plain"]);
				$response->end("500 Internal Server Error");
				$loop->stop();
			}
		});

		$socket->listen($port = $input->getOption("port"), $host = $input->getOption("host"));

		echo "Listening to {$host}:{$port}\n";

		$loop->run();
	}

	private function send(Response $res, SymfonyResponse $symfonyResponse)
	{
		$headers = $symfonyResponse->headers->allPreserveCase();
		$headers["X-Powered-By"] = "Love";

		$cookies = $symfonyResponse->headers->getCookies();
		if (count($cookies)) {
			$headers["Set-Cookie"] = [];
			foreach ($symfonyResponse->headers->getCookies() as $cookie) {
				$headers["Set-Cookie"][] = (string)$cookie;
			}
		}

		$res->writeHead($symfonyResponse->getStatusCode(), $headers);
		$res->end($symfonyResponse->getContent());
	}

}
