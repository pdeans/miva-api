<?php

namespace pdeans\Miva\Api;

use pdeans\Miva\Api\Exceptions\InvalidValueExcepion;
use pdeans\Miva\Api\Exceptions\MissingRequiredValueException;

/**
 * API Auth class
 */
final class Auth
{
    /**
     * API access token
     *
     * @var string
     */
    private $access_token;

    /**
     * API authenication header name
     *
     * @var string
     */
    private $auth_header_name;

    /**
     * List of valid HMAC types
     *
     * @var array
     */
    private $hmac_list;

    /**
     * API request HMAC signature
     *
     * @var string
     */
    private $hmac_signature;

    /**
     * The HMAC type
     *
     * @var string
     */
    private $hmac_type;

    /**
     * API private key
     *
     * @var string
     */
    private $private_key;

    /**
     * Construct Auth object
     *
     * @param string $access_token The API access token
     * @param string $private_key  The API private key
     * @param string $hmac_type    The HMAC type
     */
    public function __construct(string $access_token, string $private_key, string $hmac_type = 'sha256')
    {
        $this->access_token = $access_token;
        $this->private_key  = $private_key;

        $this->setHmacList();
        $this->setHmacType($hmac_type);

        $this->auth_header_name = 'X-Miva-API-Authorization';
        $this->hmac_signature  = null;
    }

    /**
     * Create an API authorization header
     *
     * @param string $data  The API request body
     *
     * @return string
     */
    public function createAuthHeader(string $data)
    {
        return sprintf('%s: %s', $this->auth_header_name, $this->createAuthHeaderValue($data));
    }

    /**
     * Create an API authorization header value
     *
     * @param string $data  The API request body
     *
     * @return string
     */
    public function createAuthHeaderValue(string $data)
    {
        if ((string)$this->hmac_type === '') {
            return sprintf('MIVA %s', $this->access_token);
        }

        $this->hmac_signature = $this->createHmacSignature($data);

        return sprintf(
            'MIVA-HMAC-%s %s:%s',
            strtoupper($this->hmac_type),
            $this->access_token,
            base64_encode($this->hmac_signature)
        );
    }

    /**
     * Generate a keyed hash value using the HMAC type
     *
     * @param string $data  The message to be hashed
     *
     * @return string
     */
    protected function createHmacSignature(string $data)
    {
        return hash_hmac($this->hmac_type, $data, base64_decode($this->private_key), true);
    }

    /**
     * Get the API authorization header
     *
     * @param string $data  The API request body
     *
     * @return array
     */
    public function getAuthHeader(string $data)
    {
        return [$this->auth_header_name => $this->createAuthHeaderValue($data)];
    }

    /**
     * Set the list of valid HMAC types
     */
    protected function setHmacList()
    {
        $this->hmac_list = [
            'sha1',
            'sha256',
        ];

        return $this;
    }

    /**
     * Set the HMAC type
     *
     * @param string $hmac_type
     */
    protected function setHmacType(string $hmac_type)
    {
        if (!$this->hmac_list) {
            $this->setHmacList();
        }

        if ($hmac_type === '' || (is_string($this->private_key) && $this->private_key === '')) {
            $this->hmac_type = '';
        } else {
            $hmac_type_formatted = strtolower($hmac_type);

            if (!in_array($hmac_type_formatted, $this->hmac_list)) {
                throw new InvalidValueExcepion(
                    sprintf(
                        'Invalid HMAC type "%s" provided. Valid HMAC types: "%s".',
                        $hmac_type,
                        implode('", "', $this->hmac_list)
                    )
                );
            }

            $this->hmac_type = $hmac_type_formatted;
        }

        return $this;
    }
}
