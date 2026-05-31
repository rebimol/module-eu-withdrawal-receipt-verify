<?php
/**
 * Copyright © MageMe. All rights reserved.
 * See LICENSE for license terms, or https://mageme.com/license.
 */
declare(strict_types=1);

namespace MageMe\EUWithdrawalReceiptVerify\Model\Receipt;

use MageMe\EUWithdrawal\Api\Receipt\ContentHasherInterface;
use MageMe\EUWithdrawal\Model\Receipt\ReceiptDto;

class ContentHasher implements ContentHasherInterface
{
    /**
     * Constructor.
     *
     * @param ReceiptCanonicalizer $canonicalizer
     */
    public function __construct(
        private readonly ReceiptCanonicalizer $canonicalizer,
    ) {
    }

    /**
     * Hash.
     *
     * @param ReceiptDto $dto
     * @return string
     */
    public function hash(ReceiptDto $dto): string
    {
        return hash('sha256', $this->canonicalizer->canonicalize($dto));
    }
}
