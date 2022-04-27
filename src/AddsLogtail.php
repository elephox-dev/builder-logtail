<?php
declare(strict_types=1);

namespace Elephox\Builder\Logtail;

use Elephox\Builder\RequestLogging\LoggingMiddleware;
use Elephox\Configuration\Contract\Configuration;
use Elephox\DI\Contract\ServiceCollection;
use Elephox\Logging\Contract\Sink;
use Elephox\Logging\SingleSinkLogger;
use Elephox\Logging\Vendors\Logtail\LogtailClient;
use Elephox\Logging\Vendors\Logtail\LogtailConfiguration;
use Elephox\Logging\Vendors\Logtail\LogtailSink;
use Elephox\Web\ConfigurationException;
use Elephox\Web\RequestPipelineBuilder;
use Psr\Log\LoggerInterface;

trait AddsLogtail
{
    abstract protected function getServices(): ServiceCollection;

    abstract protected function getPipeline(): RequestPipelineBuilder;

    public function addLogtail(): void
    {
        $this->getServices()->addSingleton(LogtailConfiguration::class, implementationFactory: static function (Configuration $config) {
            /** @var scalar|null $token */
            $token = $config['logtail:token'] ?? null;
            if (!is_string($token)) {
                throw new ConfigurationException('Logtail configuration error: "logtail:token" must be a string.');
            }

            $endpoint = $config['logtail:endpoint'] ?? LogtailConfiguration::DEFAULT_ENDPOINT;
            if (!is_string($endpoint)) {
                throw new ConfigurationException('Logtail configuration error: "logtail:endpoint" must be a string.');
            }

            return new LogtailConfiguration($token, $endpoint);
        });
        $this->getServices()->addSingleton(LogtailClient::class);
        $this->getServices()->addSingleton(Sink::class, LogtailSink::class, replace: true);
        $this->getServices()->addSingleton(LoggerInterface::class, SingleSinkLogger::class, replace: true);

        if ($this->getServices()->has(LoggingMiddleware::class)) {
            $middleware = $this->getServices()->requireService(LoggingMiddleware::class);
        } else {
            $middleware = $this->getServices()->resolver()->instantiate(LoggingMiddleware::class);
        }

        $this->getPipeline()->push($middleware);
    }
}
