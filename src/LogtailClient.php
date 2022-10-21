<?php
declare(strict_types=1);

namespace Elephox\Builder\Logtail;

use JsonException;

interface LogtailClient
{
	/**
	 * @throws JsonException
	 */
	public function submit(array $data): void;
}
