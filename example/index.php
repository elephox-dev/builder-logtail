<?php
declare(strict_types=1);

namespace App;

use Elephox\Builder\Logtail\AddsLogtail;
use Elephox\Http\Contract\Request;
use Elephox\Http\Contract\ResponseBuilder;
use Elephox\Http\Response;
use Elephox\Http\ResponseCode;
use Elephox\Web\Contract\RequestPipelineEndpoint;
use Elephox\Web\WebApplicationBuilder;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class WebAppBuilder extends WebApplicationBuilder {
	use AddsLogtail;
}

$builder = WebAppBuilder::create();
$builder->addLogtail();
$builder->pipeline->endpoint(new class implements RequestPipelineEndpoint {
	public function handle(Request $request): ResponseBuilder {
		return Response::build()
			->responseCode(ResponseCode::OK)
			->jsonBody(["message" => "Hello World!"]);
	}
});
$builder->build()->run();
