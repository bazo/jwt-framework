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

namespace Jose\Component\Signature\Algorithm;

use Jose\Component\Core\AlgorithmInterface;
use Jose\Component\Core\JWK;

/**
 * This interface is used by algorithms that have capabilities to sign data and verify a signature.
 */
interface SignatureAlgorithmInterface extends AlgorithmInterface
{
    /**
     * Sign the input.
     *
     * @param JWK    $key   The private key used to sign the data
     * @param string $input The input
     *
     * @throws \Exception If key does not support the algorithm or if the key usage does not authorize the operation
     *
     * @return string
     */
    public function sign(JWK $key, string $input): string;

    /**
     * Verify the signature of data.
     *
     * @param JWK    $key       The private key used to sign the data
     * @param string $input     The input
     * @param string $signature The signature to verify
     *
     * @throws \Exception If key does not support the algorithm or if the key usage does not authorize the operation
     *
     * @return bool
     */
    public function verify(JWK $key, string $input, string $signature): bool;
}