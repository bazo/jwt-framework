<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Jose\Component\Encryption\Algorithm\KeyEncryption;

use Base64Url\Base64Url;
use Jose\Component\Core\JWK;

/**
 * Class PBES2AESKW.
 */
abstract class PBES2AESKW implements KeyWrappingInterface
{
    /**
     * @var int
     */
    private $salt_size;

    /**
     * @var int
     */
    private $nb_count;

    /**
     * @param int $salt_size
     * @param int $nb_count
     */
    public function __construct(int $salt_size = 64, int $nb_count = 4096)
    {
        $this->salt_size = $salt_size;
        $this->nb_count = $nb_count;
    }

    /**
     * {@inheritdoc}
     */
    public function allowedKeyTypes(): array
    {
        return ['oct'];
    }

    /**
     * {@inheritdoc}
     */
    public function wrapKey(JWK $key, string $cek, array $complete_headers, array &$additional_headers): string
    {
        $this->checkKey($key);
        $this->checkHeaderAlgorithm($complete_headers);
        $wrapper = $this->getWrapper();
        $hash_algorithm = $this->getHashAlgorithm();
        $key_size = $this->getKeySize();
        $salt = random_bytes($this->salt_size);
        $password = Base64Url::decode($key->get('k'));

        // We set headers parameters
        $additional_headers['p2s'] = Base64Url::encode($salt);
        $additional_headers['p2c'] = $this->nb_count;

        $derived_key = hash_pbkdf2($hash_algorithm, $password, $complete_headers['alg']."\x00".$salt, $this->nb_count, $key_size, true);

        return $wrapper::wrap($derived_key, $cek);
    }

    /**
     * {@inheritdoc}
     */
    public function unwrapKey(JWK $key, string $encrypted_cek, array $complete_headers): string
    {
        $this->checkKey($key);
        $this->checkHeaderAlgorithm($complete_headers);
        $this->checkHeaderAdditionalParameters($complete_headers);
        $wrapper = $this->getWrapper();
        $hash_algorithm = $this->getHashAlgorithm();
        $key_size = $this->getKeySize();
        $salt = $complete_headers['alg']."\x00".Base64Url::decode($complete_headers['p2s']);
        $count = $complete_headers['p2c'];
        $password = Base64Url::decode($key->get('k'));

        $derived_key = hash_pbkdf2($hash_algorithm, $password, $salt, $count, $key_size, true);

        return $wrapper::unwrap($derived_key, $encrypted_cek);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyManagementMode(): string
    {
        return self::MODE_WRAP;
    }

    /**
     * @param JWK $key
     */
    protected function checkKey(JWK $key)
    {
        if ('oct' !== $key->get('kty')) {
            throw new \InvalidArgumentException('Wrong key type.');
        }
        if (!$key->has('k')) {
            throw new \InvalidArgumentException('The key parameter "k" is missing.');
        }
    }

    /**
     * @param array $header
     */
    protected function checkHeaderAlgorithm(array $header)
    {
        if (!array_key_exists('alg', $header)) {
            throw new \InvalidArgumentException('The header parameter "alg" is missing.');
        }
        if (!is_string($header['alg'])) {
            throw new \InvalidArgumentException('The header parameter "alg" is not valid.');
        }
    }

    /**
     * @param array $header
     */
    protected function checkHeaderAdditionalParameters(array $header)
    {
        foreach (['p2s', 'p2c'] as $k) {
            if (!array_key_exists($k, $header)) {
                throw new \InvalidArgumentException(sprintf('The header parameter "%s" is missing.', $k));
            }
            if (empty($header[$k])) {
                throw new \InvalidArgumentException(sprintf('The header parameter "%s" is not valid.', $k));
            }
        }
    }

    /**
     * @return \AESKW\A128KW|\AESKW\A192KW|\AESKW\A256KW
     */
    abstract protected function getWrapper();

    /**
     * @return string
     */
    abstract protected function getHashAlgorithm(): string;

    /**
     * @return int
     */
    abstract protected function getKeySize(): int;
}