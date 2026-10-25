<?php
/**
 * Copyright © MageMe. All rights reserved.
 * See LICENSE for license terms, or https://mageme.com/license.
 */
declare(strict_types=1);

namespace MageMe\EUWithdrawalReceiptVerify\Controller\Verify;

use MageMe\EUWithdrawal\Api\Receipt\ContentHasherInterface;
use MageMe\EUWithdrawal\Api\RequestRepositoryInterface;
use MageMe\EUWithdrawal\Model\Receipt\ReceiptBuilder;
use MageMe\EUWithdrawal\Model\Security\RateLimiter;
use MageMe\EUWithdrawal\Model\Security\ResponseTimer;
use MageMe\Core\Controller\AbstractStorefrontGetPage;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class Index extends AbstractStorefrontGetPage
{
    /**
     * Constructor.
     *
     * @param Context $context
     * @param RequestInterface $request
     * @param PageFactory $pageFactory
     * @param ReceiptBuilder $builder
     * @param ContentHasherInterface $hasher
     * @param RateLimiter $rateLimiter
     * @param ResponseTimer $timer
     * @param OrderRepositoryInterface $orderRepository
     * @param RequestRepositoryInterface $requestRepository
     */
    public function __construct(
        Context $context,
        private readonly RequestInterface $request,
        private readonly PageFactory $pageFactory,
        private readonly ReceiptBuilder $builder,
        private readonly ContentHasherInterface $hasher,
        private readonly RateLimiter $rateLimiter,
        private readonly ResponseTimer $timer,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RequestRepositoryInterface $requestRepository,
    ) {
        parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return mixed
     */
    public function execute(): mixed
    {
        $this->timer->start();
        $page = $this->pageFactory->create();
        $block = $page->getLayout()->getBlock('verify.result');

        $rawId   = (string) $this->request->getParam('request_id', '');
        $rawHash = (string) $this->request->getParam('hash', '');

        $ok = false;
        $payload = [];

        if (preg_match('/^\d+$/', $rawId) === 1 && preg_match('/^[a-f0-9]{64}$/', $rawHash) === 1) {
            if ($this->rateLimiter->allow($this->ipHash())) {
                try {
                    $dto = $this->builder->build((int) $rawId);
                    $computed = $this->hasher->hash($dto);
                    if (hash_equals($computed, $rawHash)) {
                        $ok = true;
                        $order = $this->resolveOrder($dto->requestId);
                        $payload = [
                            'request_id'     => $dto->requestId,
                            'created_at'     => (string) ($dto->receipt['created_at'] ?? ''),
                            'order_number'   => (string) ($dto->order['increment_id'] ?? ''),
                            'consumer_name'  => (string) ($dto->consumer['name'] ?? ''),
                            'items'          => $dto->items,
                            'refund'         => $dto->refund,
                            'merchant_name'  => (string) ($dto->merchant['name'] ?? ''),
                            'locale'         => (string) ($dto->receipt['locale'] ?? 'en_US'),
                            'article_ref'    => (string) ($dto->legal['article_ref'] ?? ''),
                            'content_hash'   => $rawHash,
                            'currency_code'  => $order !== null ? (string) $order->getOrderCurrencyCode() : '',
                            'store_id'       => $order !== null ? (int) $order->getStoreId() : 0,
                        ];
                    }
                } catch (\Throwable) {
                    // uniform fail
                }
            }
        }

        if ($block) {
            $block->setData('ok', $ok);
            foreach ($payload as $k => $v) {
                $block->setData($k, $v);
            }
        }

        $this->timer->pad(250);

        // AbstractResult::setHeader is the public API for attaching response
        // headers to a Result\Page; $page->getResponse() doesn't exist on
        // the interceptor and was the source of the 500 on /verify/index/.
        $page->setHeader('Cache-Control', 'no-store', true);
        $page->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        return $page;
    }

    /**
     * Ip hash.
     *
     * @return string
     */
    private function ipHash(): string
    {
        $remote = $this->request->getServer('REMOTE_ADDR') ?? '';
        return hash('sha256', (string) $remote);
    }

    /**
     * Look up the request's own order. Verify-page amount formatting must use
     * the currency the consumer was actually charged in
     * (sales_order.order_currency_code), not the store base currency — the
     * receipt is a customer-facing artefact, and a € invoice displayed as $
     * leaves the consumer unable to reconcile the figure against their bank
     * statement. The order's store also sets the timezone of the submission time.
     *
     * Returns null on any lookup failure; the block then uses the store's base
     * currency and the current store's timezone.
     */
    private function resolveOrder(int $requestId): ?OrderInterface
    {
        try {
            return $this->orderRepository->get((int) $this->requestRepository->get($requestId)->getOrderId());
        } catch (\Throwable) {
            // Silent — block falls back to base currency and current store.
        }
        return null;
    }
}
