<?php
declare(strict_types=1);

namespace Elephox\Builder\Logtail;

class LogtailConfiguration
{
	public const DEFAULT_ENDPOINT = 'https://in.logtail.com';
	public const DEFAULT_BUFFERED_LIMIT = 10;

	public function __construct(
		public readonly string $sourceToken,
		public readonly string $endpoint = self::DEFAULT_ENDPOINT,
		public readonly int $bufferedLimit = self::DEFAULT_BUFFERED_LIMIT,
	) {
	}
}
