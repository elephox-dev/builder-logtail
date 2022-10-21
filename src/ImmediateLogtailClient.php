<?php
declare(strict_types=1);

namespace Elephox\Builder\Logtail;

class ImmediateLogtailClient extends AbstractLogtailClient
{
	public function submit(array $data): void
	{
		$this->sendData($data);
	}
}
