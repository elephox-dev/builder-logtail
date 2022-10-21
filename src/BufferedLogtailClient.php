<?php
declare(strict_types=1);

namespace Elephox\Builder\Logtail;

use JsonException;

class BufferedLogtailClient extends AbstractLogtailClient
{
	protected array $buffer = [];

	public function submit(array $data): void {
		$this->buffer[] = $data;

		if (count($this->buffer) >= $this->configuration->bufferedLimit) {
			$this->send();
		}
	}

	/**
	 * @throws JsonException
	 */
	protected function send(): void {
		$this->sendData($this->buffer);
	}

	public function __destruct() {
		if (!empty($this->buffer)) {
			try {
				$this->send();
			} catch (JsonException $e) {
				trigger_error("Unable to encode remaining buffer: " . $e->getMessage());
			}
		}
	}
}
