<?php
declare(strict_types=1);

namespace MageMe\EUWithdrawalReceiptVerify\Controller\Verify;

use MageMe\EUWithdrawal\Api\Receipt\ContentHasherInterface;
use MageMe\EUWithdrawal\Model\Receipt\ReceiptBuilder;
use MageMe\EUWithdrawal\Model\Security\AntiEnumeration;
use MageMe\EUWithdrawal\Model\Security\RateLimiter;
use MageMe\EUWithdrawal\Model\Security\ResponseTimer;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index implements HttpGetActionInterface
{
    /**
     * Constructor.
     *
     * @param RequestInterface $request
     * @param PageFactory $pageFactory
     * @param ReceiptBuilder $builder
     * @param ContentHasherInterface $hasher
     * @param RateLimiter $rateLimiter
     * @param ResponseTimer $timer
     * @param AntiEnumeration $antiEnumeration
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly PageFactory $pageFactory,
        private readonly ReceiptBuilder $builder,
        private readonly ContentHasherInterface $hasher,
        private readonly RateLimiter $rateLimiter,
        private readonly ResponseTimer $timer,
        private readonly AntiEnumeration $antiEnumeration,
    ) {
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
                        $payload = [
                            'request_id'     => $dto->requestId,
                            'confirmed_at'   => (string) ($dto->receipt['confirmed_at'] ?? ''),
                            'order_number'   => (string) ($dto->order['increment_id'] ?? ''),
                            'consumer_name'  => (string) ($dto->consumer['name'] ?? ''),
                            'items'          => $dto->items,
                            'refund'         => $dto->refund,
                            'merchant_name'  => (string) ($dto->merchant['name'] ?? ''),
                            'locale'         => (string) ($dto->receipt['locale'] ?? 'en_US'),
                            'article_ref'    => (string) ($dto->legal['article_ref'] ?? ''),
                            'content_hash'   => $rawHash,
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
}
