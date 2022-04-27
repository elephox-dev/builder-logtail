<?php
declare(strict_types=1);

namespace Elephox\Builder\Logtail;

class LogtailConfiguration
{
	public const DEFAULT_ENDPOINT = 'https://in.logtail.com';

	public function __construct(
		public readonly string $sourceToken,
		public readonly string $endpoint = self::DEFAULT_ENDPOINT,
	) {
	}
}
