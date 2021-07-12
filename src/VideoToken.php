<?php

declare(strict_types=1);

namespace Baraja\VideoToken;


final class VideoToken
{
	public const
		PROVIDER_YOUTUBE = 'youtube',
		PROVIDER_VIMEO = 'vimeo';

	private string $token;

	private string $provider;


	public function __construct(string $token, ?string $provider = null)
	{
		$parser = $this->checkVideoToken(trim($token), $provider ? strtolower($provider) : null);
		if (strlen($parser['token']) > 32) {
			throw new \InvalidArgumentException('Video token "' . $token . '" is too long.');
		}
		$this->token = $parser['token'];
		$this->provider = $parser['provider'];
	}


	public function getToken(): string
	{
		return $this->token;
	}


	public function getProvider(): string
	{
		return $this->provider;
	}


	public function getUrl(): string
	{
		if ($this->provider === self::PROVIDER_VIMEO) {
			return 'https://player.vimeo.com/video/' . urlencode($this->token);
		}
		if ($this->provider === self::PROVIDER_YOUTUBE) {
			return 'https://www.youtube.com/embed/' . urlencode($this->token) . '?rel=0';
		}

		throw new \LogicException('Provider "' . $this->provider . '" is not supported.');
	}


	public function getThumbnailUrl(): ?string
	{
		if ($this->provider === self::PROVIDER_YOUTUBE) {
			return 'https://img.youtube.com/vi/' . urlencode($this->token) . '/maxresdefault.jpg';
		}
		if ($this->provider === self::PROVIDER_VIMEO) {
			$api = trim(
				(string) @file_get_contents(
					'https://vimeo.com/api/oembed.json?url=https%3A//vimeo.com/' . urlencode($this->token)
				)
			);

			return json_decode($api, true)['thumbnail_url'] ?? null;
		}

		return null;
	}


	/**
	 * Find YouTube token by real URI
	 *
	 * 1. User channel with URI prefix (no token)
	 * 2. Exact match
	 * 3. URI prefix + token
	 * 4. Token + organic URL parameters
	 * 5. User channel (no token)
	 * 6. URL encoded like API
	 * 7. Special cases
	 */
	public static function parseYouTubeTokenByUrl(string $url): ?string
	{
		if (str_starts_with($url, 'user/')) { // 1.
			return null;
		}
		if (preg_match('/^[a-zA-Z0-9\-_]{11}$/', $url) === 1) { // 2.
			return $url;
		}
		if (
			preg_match(
				'/^(?:watch\?v=|v\/|embed\/|ytscreeningroom\?v=|\?v=|\?vi=|e\/|watch\?.*vi?=|\?feature=[a-z_]*&v=|vi\/)([a-zA-Z0-9\-_]{11})/',
				$url,
				$regularMatch
			) === 1
		) { // 3.
			return $regularMatch[1] ?? null;
		}
		if (preg_match('/^([a-zA-Z0-9\-_]{11})(?:\?[a-z]|&[a-z])/', $url, $organicParametersMatch) === 1) { // 4.
			return $organicParametersMatch[1];
		}
		if (preg_match('/u\/1\/([a-zA-Z0-9\-_]{11})(?:\?rel=0)?$/', $url) === 1) { // 5.
			return null; // 5. User channel without token.
		}
		if (preg_match('/(?:watch%3Fv%3D|watch\?v%3D)([a-zA-Z0-9\-_]{11})[%&]/', $url, $urlEncoded) === 1) { // 6.
			return $urlEncoded[1] ?? null;
		}
		if (preg_match('/^watchv=([a-zA-Z0-9\-_]{11})&list=/', $url, $special1) === 1) { // 7. Rules for special cases
			return $special1[1] ?? null;
		}

		return null;
	}


	/**
	 * @return array{token: string, provider: string}
	 */
	private function checkVideoToken(string $token, ?string $provider = null): array
	{
		$parsedToken = null;
		$parsedProvider = null;

		if (preg_match('/^(?:https?:\/\/|\/\/)(?:www\.)?(.+)$/', $token, $urlParser) === 1) { // URL
			if (
				preg_match(
					'/^(?:youtube\.com|youtu\.be|youtube-nocookie\.com|yt\.be)\/(.+)$/',
					$urlParser[1],
					$youTubeParser,
				) === 1
			) {
				$parsedProvider = self::PROVIDER_YOUTUBE;
				$parsedToken = self::parseYouTubeTokenByUrl($youTubeParser[1] ?? '');
			} elseif (
				preg_match(
					'/(?:(?:player\.)?vimeo\.com\/(?:video\/)?)?(?<token>\d{8})/',
					$urlParser[1],
					$vimeoParser,
				) === 1
			) {
				$parsedProvider = self::PROVIDER_VIMEO;
				$parsedToken = (string) $vimeoParser['token'];
			} else {
				throw new \InvalidArgumentException('Token or URL "' . $token . '" is invalid.');
			}
		}
		if (preg_match('/embed\/([a-zA-Z0-9\-_]{11})"/', $token, $youTubeEmbed) === 1) {
			$parsedProvider = self::PROVIDER_YOUTUBE;
			$parsedToken = (string) $youTubeEmbed[1];
		}
		if ($parsedProvider !== null && $parsedToken === null) { // Invalid input
			throw new \InvalidArgumentException('Token can not be parser for "' . $parsedProvider . '" provider.');
		}
		$token = $parsedToken ?? $token;
		$provider = $parsedProvider ?? $provider;
		$providerHint = $this->resolveProviderByToken($token);
		if ($provider !== $providerHint && $providerHint !== null) {
			$provider = $providerHint;
		}
		if ($provider === null) {
			throw new \InvalidArgumentException('Provider for token "' . $token . '" is mandatory.');
		}

		return [
			'token' => $token,
			'provider' => $provider,
		];
	}


	private function resolveProviderByToken(?string $token): ?string
	{
		if ($token === null) {
			return null;
		}
		if (preg_match('/^\d+$/', $token) === 1) {
			return self::PROVIDER_VIMEO;
		}
		if (preg_match('/^[a-zA-Z0-9\-_]{11}$/', $token) === 1) {
			return self::PROVIDER_YOUTUBE;
		}

		return null;
	}
}
