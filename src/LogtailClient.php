<?php
declare(strict_types=1);

namespace Elephox\Builder\Logtail;

use JsonException;
use LogicException;
use CurlHandle;
use RuntimeException;
use function curl_init;
use function curl_setopt;

/**
 * Format JSON records for Logtail
 *
 * Forked from the original LogtailClient.php in the logtail/monolog-logtail project.
 *
 * @copyright Better Stack, 2022 Logtail
 *
 * @see https://github.com/logtail/monolog-logtail/blob/d1c5675e0ca6ed7b37de70e70eb305713cee034d/src/Monolog/LogtailClient.php
 */
class LogtailClient
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

	private ?CurlHandle $handle = null;

	public function __construct(private readonly LogtailConfiguration $configuration)
	{
	}

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
	public function send(array $data): void
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
