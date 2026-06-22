<?php
/**
 * Copyright © MageMe. All rights reserved.
 * See LICENSE for license terms, or https://mageme.com/license.
 */
declare(strict_types=1);

namespace MageMe\EUWithdrawalReceiptVerify\Block\Withdraw;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class VerifyResult extends Template
{
    /**
     * Constructor.
     *
     * @param Context $context
     * @param TimezoneInterface $timezone
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly TimezoneInterface $timezone,
        private readonly PriceCurrencyInterface $priceCurrency,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Is ok.
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return (bool) $this->getData('ok');
    }

    /**
     * Get request id.
     *
     * @return ?int
     */
    public function getRequestId(): ?int
    {
        $v = $this->getData('request_id');
        return $v !== null ? (int) $v : null;
    }

    /** Human-facing zero-padded withdrawal number derived from request_id. */
    public function getIncrementIdDisplay(): string
    {
        $id = $this->getRequestId();
        return $id !== null ? sprintf('%09d', $id) : '';
    }

    /**
     * Get order number.
     *
     * @return string
     */
    public function getOrderNumber(): string
    {
        return (string) $this->getData('order_number');
    }

    /**
     * Get consumer name.
     *
     * @return string
     */
    public function getConsumerName(): string
    {
        return (string) $this->getData('consumer_name');
    }

    /**
     * Get merchant name.
     *
     * @return string
     */
    public function getMerchantName(): string
    {
        return (string) $this->getData('merchant_name');
    }

    /**
     * Get article ref.
     *
     * @return string
     */
    public function getArticleRef(): string
    {
        return (string) $this->getData('article_ref');
    }

    /**
     * Get content hash.
     *
     * @return string
     */
    public function getContentHash(): string
    {
        return (string) $this->getData('content_hash');
    }

    /** Formats the ReceiptDto's ISO created_at ("2026-04-24T11:03:22Z") in the store timezone. */
    public function getCreatedAtFormatted(): string
    {
        $raw = (string) $this->getData('created_at');
        if ($raw === '') {
            return '';
        }
        try {
            $dt = new \DateTimeImmutable($raw, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return $raw;
        }
        return $this->timezone->formatDateTime(
            $dt,
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::SHORT,
        );
    }

    /**
     * @return array<int, array{order_item_id:int,sku:string,qty:int,refund_amount:string}>
     */
    public function getItems(): array
    {
        $items = $this->getData('items');
        return is_array($items) ? $items : [];
    }

    /** @return array{items:string,shipping:string,tax:string,total:string} */
    public function getRefund(): array
    {
        $r = $this->getData('refund');
        if (!is_array($r)) {
            return ['items' => '0.00', 'shipping' => '0.00', 'tax' => '0.00', 'total' => '0.00'];
        }
        return [
            'items'    => (string) ($r['items'] ?? '0.00'),
            'shipping' => (string) ($r['shipping'] ?? '0.00'),
            'tax'      => (string) ($r['tax'] ?? '0.00'),
            'total'    => (string) ($r['total'] ?? '0.00'),
        ];
    }

    /**
     * Get currency code.
     *
     * Resolved by the controller from `sales_order.order_currency_code`
     * (whatever the consumer was actually charged in). Falls through to the
     * empty string when the lookup failed; in that case `formatAmount()`
     * defers to PriceCurrency's default which uses the store's base
     * currency.
     *
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return (string) ($this->getData('currency_code') ?: '');
    }

    /**
     * Format amount.
     *
     * Always uses the order's currency (passed in by the controller via
     * `setData('currency_code', ...)`) so the verify page reconciles 1:1
     * against the receipt the consumer actually received. When unresolved,
     * defers to the store's base currency.
     *
     * @param string $amount
     * @param string $currencyCode override (optional — falls back to order currency)
     * @return string
     */
    public function formatAmount(string $amount, string $currencyCode = ''): string
    {
        $code = $currencyCode !== '' ? $currencyCode : $this->getCurrencyCode();
        return (string) $this->priceCurrency->format(
            (float) $amount,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $code !== '' ? $code : null,
        );
    }

    /**
     * Renders the SHA-256 hash split into 8-char groups for readability —
     * "123bf4a5 f5ef6528 96587dce ..." instead of one unbroken 64-char run.
     * Easier to read aloud / compare manually than the dense form.
     */
    public function getContentHashDisplay(): string
    {
        $hash = $this->getContentHash();
        if ($hash === '') {
            return '';
        }
        return trim((string) chunk_split($hash, 8, ' '));
    }

    /**
     * Get my account url.
     *
     * @return string
     */
    public function getMyAccountUrl(): string
    {
        return $this->getUrl('customer/account/');
    }
}
