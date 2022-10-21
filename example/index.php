<?php
declare(strict_types=1);

namespace App;

use Closure;
use Elephox\Builder\Logtail\AddsLogtail;
use Elephox\Http\Contract\Request;
use Elephox\Http\Contract\ResponseBuilder;
use Elephox\Http\Response;
use Elephox\Http\ResponseCode;
use Elephox\Web\Contract\RequestPipelineEndpoint;
use Elephox\Web\Contract\WebMiddleware;
use Elephox\Web\WebApplicationBuilder;
use Psr\Log\LoggerInterface;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class WebAppBuilder extends WebApplicationBuilder {
	use AddsLogtail;
}

class HelloWorldEndpoint implements RequestPipelineEndpoint {
	public function handle(Request $request): ResponseBuilder {
		return Response::build()
			->responseCode(ResponseCode::OK)
			->jsonBody(["message" => "Hello World!"]);
	}
}

class VerboseLogger implements WebMiddleware {
	public function __construct(private readonly LoggerInterface $logger) {}

	public function handle(Request $request, Closure $next): ResponseBuilder {
		$this->logger->info("Handling request in VerboseLogger");

		$response = $next($request);

		$this->logger->info("Request handled in VerboseLogger");

		return $response;
	}
}

$builder = WebAppBuilder::create();
$builder->addLogtail();
$builder->pipeline->endpoint(new HelloWorldEndpoint());
$builder->pipeline->push(VerboseLogger::class);
$builder->build()->run();
