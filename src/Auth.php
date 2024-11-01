<?php

namespace pdeans\Miva\Api;

use pdeans\Miva\Api\Exceptions\InvalidValueException;

/**
 * API Auth class
 */
final class Auth
{
    /**
     * API access token.
     *
     * @var string
     */
    private string $accessToken;

    /**
     * API authentication header name.
     *
     * @var string
     */
    private string $authHeaderName;

    /**
     * List of valid HMAC types.
     *
     * @var array
     */
    private array $hmacList;

    /**
     * API request HMAC signature.
     *
     * @var string
     */
    private string $hmacSignature;

    /**
     * The HMAC type.
     *
     * @var string
     */
    private string $hmacType;

    /**
     * API private key.
     *
     * @var string
     */
    private string $privateKey;

    /**
     * Create a new API auth instance.
     */
    public function __construct(string $accessToken, string $privateKey, string $hmacType = 'sha256')
    {
        $this->accessToken = $accessToken;
        $this->privateKey = $privateKey;

        $this->setHmacList();
        $this->setHmacType($hmacType);

        $this->authHeaderName = 'X-Miva-API-Authorization';
        $this->hmacSignature = '';
    }

    /**
     * Create an API authorization header.
     */
    public function createAuthHeader(string $data): string
    {
        return sprintf('%s: %s', $this->authHeaderName, $this->createAuthHeaderValue($data));
    }

    /**
     * Create an API authorization header value.
     */
    public function createAuthHeaderValue(string $data): string
    {
        if ((string) $this->hmacType === '') {
            return sprintf('MIVA %s', $this->accessToken);
        }

        $this->hmacSignature = $this->createHmacSignature($data);

        return sprintf(
            'MIVA-HMAC-%s %s:%s',
            strtoupper($this->hmacType),
            $this->accessToken,
            base64_encode($this->hmacSignature)
        );
    }

    /**
     * Generate a keyed hash value using the HMAC type.
     */
    protected function createHmacSignature(string $data): string
    {
        return hash_hmac($this->hmacType, $data, base64_decode($this->privateKey), true);
    }

    /**
     * Get the API authorization header.
     */
    public function getAuthHeader(string $data): array
    {
        return [$this->authHeaderName => $this->createAuthHeaderValue($data)];
    }

    /**
     * Set the list of valid HMAC types
     */
    protected function setHmacList(): static
    {
        $this->hmacList = [
            'sha1',
            'sha256',
        ];

        return $this;
    }

    /**
     * Set the HMAC type.
     */
    protected function setHmacType(string $hmacType): static
    {
        if (!$this->hmacList) {
            $this->setHmacList();
        }

        if ($hmacType === '' || (is_string($this->privateKey) && $this->privateKey === '')) {
            $this->hmacType = '';
        } else {
            $hmacTypeFormatted = strtolower($hmacType);

            if (!in_array($hmacTypeFormatted, $this->hmacList)) {
                throw new InvalidValueException(
                    sprintf(
                        'Invalid HMAC type "%s" provided. Valid HMAC types: "%s".',
                        $hmacType,
                        implode('", "', $this->hmacList)
                    )
                );
            }

            $this->hmacType = $hmacTypeFormatted;
        }

        return $this;
    }
}
