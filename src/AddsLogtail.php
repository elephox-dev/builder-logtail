<?php
declare(strict_types=1);

namespace Elephox\Builder\Logtail;

use Elephox\Builder\RequestLogging\LoggingMiddleware;
use Elephox\Configuration\Contract\Configuration;
use Elephox\DI\Contract\ServiceCollection;
use Elephox\Logging\MultiSinkLogger;
use Elephox\Logging\SingleSinkLogger;
use Elephox\Web\ConfigurationException;
use Elephox\Web\RequestPipelineBuilder;
use Psr\Log\LoggerInterface;

trait AddsLogtail
{
	abstract protected function getServices(): ServiceCollection;

	abstract protected function getPipeline(): RequestPipelineBuilder;

	public function addLogtail(bool $buffered = true): void
	{
		$services = $this->getServices();
		$services->addSingleton(LogtailConfiguration::class, factory: static function (Configuration $config) {
			/** @var scalar|null $token */
			$token = $config['logtail:token'] ?? null;
			if (!is_string($token)) {
				throw new ConfigurationException('Logtail configuration error: "logtail:token" must be a string.');
			}

			$endpoint = $config['logtail:endpoint'] ?? LogtailConfiguration::DEFAULT_ENDPOINT;
			if (!is_string($endpoint)) {
				throw new ConfigurationException('Logtail configuration error: "logtail:endpoint" must be a string.');
			}

			$bufferedLimitStr = (string)($config['logtail:bufferedLimit'] ?? LogtailConfiguration::DEFAULT_BUFFERED_LIMIT);
			if (!ctype_digit($bufferedLimitStr)) {
				throw new ConfigurationException('Logtail configuration error: "logtail:bufferedLimit" must be a string consisting of digits.');
			}

			return new LogtailConfiguration($token, $endpoint, (int)$bufferedLimitStr);
		});

		$services->addSingleton(LogtailClient::class, match ($buffered) {
			true => BufferedLogtailClient::class,
			false => ImmediateLogtailClient::class,
		});

		$logger = $services->getService(LoggerInterface::class);
		$logtailSink = $services->resolver()->instantiate(LogtailSink::class);
		if ($logger instanceof MultiSinkLogger) {
			// add sink to multi-sink
			$logger->addSink($logtailSink);
		} else {
			// replace/add sink and logger
			$services->addSingleton(LoggerInterface::class, SingleSinkLogger::class, factory: fn () => new SingleSinkLogger($logtailSink), replace: true);
		}

		$middleware = $services->getService(LoggingMiddleware::class);
		if ($middleware === null) {
			$middleware = $services->resolver()->instantiate(LoggingMiddleware::class);
		}

		$this->getPipeline()->push($middleware);
	}
}
