<?php
declare(strict_types=1);

namespace Elephox\Builder\Logtail;

use CurlHandle;
use JsonException;
use LogicException;
use RuntimeException;

abstract class AbstractLogtailClient implements LogtailClient
{
	public const CURL_RETRYABLE_ERROR_CODES = [
		CURLE_COULDNT_RESOLVE_HOST,
		CURLE_COULDNT_CONNECT,
		CURLE_HTTP_NOT_FOUND,
		CURLE_READ_ERROR,
		CURLE_OPERATION_TIMEOUTED,
		CURLE_HTTP_POST_ERROR,
		CURLE_SSL_CONNECT_ERROR,
	];

	public function __construct(protected readonly LogtailConfiguration $configuration)
	{
	}

	private ?CurlHandle $handle = null;

	private function initCurlHandle(): CurlHandle
	{
		$handle = curl_init();
		if (!$handle) {
			throw new LogicException('Could not initialize curl handle');
		}

		$headers = [
			'Content-Type: application/json',
			"Authorization: Bearer {$this->configuration->sourceToken}",
		];

		curl_setopt($handle, CURLOPT_URL, $this->configuration->endpoint);
		curl_setopt($handle, CURLOPT_POST, true);
		curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);

		return $handle;
	}

	private function getHandle(): CurlHandle
	{
		if ($this->handle === null) {
			$this->handle = $this->initCurlHandle();
		}

		return $this->handle;
	}

	/**
	 * @throws JsonException
	 */
	protected function sendData(array $data): void
	{
		$ch = $this->getHandle();
		$retries = 5;

		$encoded = json_encode($data, JSON_THROW_ON_ERROR);

		assert(is_string($encoded));

		curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		while ($retries--) {
			$curlResponse = curl_exec($ch);
			if ($curlResponse !== false) {
				return;
			}

			$curlErrno = curl_errno($ch);

			if ($retries > 0 && in_array($curlErrno, self::CURL_RETRYABLE_ERROR_CODES, true)) {
				continue;
			}

			$curlError = curl_error($ch);

			throw new RuntimeException(sprintf('Curl error (code %d): %s', $curlErrno, $curlError));
		}
	}
}
