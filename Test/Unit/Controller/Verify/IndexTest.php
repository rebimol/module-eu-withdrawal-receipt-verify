<?php
/**
 * Copyright © MageMe. All rights reserved.
 * See LICENSE for license terms, or https://mageme.com/license.
 */
declare(strict_types=1);

namespace MageMe\EUWithdrawalReceiptVerify\Test\Unit\Controller\Verify;

use MageMe\EUWithdrawalReceiptVerify\Controller\Verify\Index;
use MageMe\EUWithdrawalReceiptVerify\Model\Receipt\ContentHasher;
use MageMe\EUWithdrawal\Model\Receipt\ReceiptBuilder;
use MageMe\EUWithdrawal\Model\Receipt\ReceiptDto;
use MageMe\EUWithdrawal\Model\Security\RateLimiter;
use MageMe\EUWithdrawal\Model\Security\ResponseTimer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    public function testBadRequestIdReturnsUniformFail(): void
    {
        $deps = $this->deps(['request_id' => 'abc', 'hash' => str_repeat('a', 64)]);
        $page = (new Index(
            $deps->context,
            $deps->request,
            $deps->pageFactory,
            $deps->builder,
            $deps->hasher,
            $deps->rateLimiter,
            $deps->timer,
            $deps->orderRepo,
            $deps->scb,
        ))->execute();
        $this->assertSame($deps->page, $page);
        $this->assertFalse((bool) $deps->block->getData('ok'));
    }

    public function testBadHashReturnsUniformFail(): void
    {
        $deps = $this->deps(['request_id' => '42', 'hash' => 'short']);
        (new Index(
            $deps->context,
            $deps->request,
            $deps->pageFactory,
            $deps->builder,
            $deps->hasher,
            $deps->rateLimiter,
            $deps->timer,
            $deps->orderRepo,
            $deps->scb,
        ))->execute();
        $this->assertFalse((bool) $deps->block->getData('ok'));
    }

    public function testRateLimitedReturnsUniformFail(): void
    {
        $deps = $this->deps(['request_id' => '42', 'hash' => str_repeat('a', 64)], allowRate: false);
        (new Index(
            $deps->context,
            $deps->request,
            $deps->pageFactory,
            $deps->builder,
            $deps->hasher,
            $deps->rateLimiter,
            $deps->timer,
            $deps->orderRepo,
            $deps->scb,
        ))->execute();
        $this->assertFalse((bool) $deps->block->getData('ok'));
    }

    public function testMatchingHashReturnsOk(): void
    {
        $hash = str_repeat('a', 64);
        $deps = $this->deps(['request_id' => '42', 'hash' => $hash]);
        $deps->builder->method('build')->willReturn($this->dto());
        $deps->hasher->method('hash')->willReturn($hash);

        (new Index(
            $deps->context,
            $deps->request,
            $deps->pageFactory,
            $deps->builder,
            $deps->hasher,
            $deps->rateLimiter,
            $deps->timer,
            $deps->orderRepo,
            $deps->scb,
        ))->execute();
        $this->assertTrue((bool) $deps->block->getData('ok'));
    }

    public function testMismatchHashReturnsUniformFail(): void
    {
        $deps = $this->deps(['request_id' => '42', 'hash' => str_repeat('a', 64)]);
        $deps->builder->method('build')->willReturn($this->dto());
        $deps->hasher->method('hash')->willReturn(str_repeat('b', 64));
        (new Index(
            $deps->context,
            $deps->request,
            $deps->pageFactory,
            $deps->builder,
            $deps->hasher,
            $deps->rateLimiter,
            $deps->timer,
            $deps->orderRepo,
            $deps->scb,
        ))->execute();
        $this->assertFalse((bool) $deps->block->getData('ok'));
    }

    private function deps(array $params, bool $allowRate = true): object
    {
        $d = new \stdClass();
        $d->context = $this->createMock(Context::class);
        $d->request = $this->createMock(HttpRequest::class);
        $d->request->method('getParam')->willReturnCallback(fn($k, $default = null) => $params[$k] ?? $default);
        $d->request->method('getServer')->willReturn('127.0.0.1');

        $d->block = new class {
            public array $data = [];
            public function setData($k, $v = null): self { $this->data[$k] = $v; return $this; }
            public function getData($k = null) { return $k === null ? $this->data : ($this->data[$k] ?? null); }
        };

        $layout = new class($d->block) {
            public function __construct(private object $b) {}
            public function getBlock($n) { return $this->b; }
            public function getOutput() { return ''; }
            public function getUpdate() { return new class { public function addUpdate($x): void {} }; }
        };

        $d->page = new class($layout) {
            public array $headers = [];
            public function __construct(public $layout) {}
            public function getLayout() { return $this->layout; }
            public function setHeader($k, $v, $r = false): void { $this->headers[$k] = $v; }
            public function getConfig() { return new class {
                public function getTitle() { return new class { public function set($t): void {} }; }
                public function setMetadata($k, $v): void {}
            }; }
        };

        $d->pageFactory = $this->createMock(PageFactory::class);
        $d->pageFactory->method('create')->willReturn($d->page);

        $d->builder = $this->createMock(ReceiptBuilder::class);
        $d->hasher  = $this->createMock(ContentHasher::class);
        $d->rateLimiter = $this->createMock(RateLimiter::class);
        $d->rateLimiter->method('allow')->willReturn($allowRate);
        $d->timer = $this->createMock(ResponseTimer::class);
        $d->orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $d->scb = $this->createMock(SearchCriteriaBuilder::class);
        return $d;
    }

    private function dto(): ReceiptDto
    {
        return new ReceiptDto(
            requestId: 42,
            consumer: ['name' => 'A', 'email' => 'a@x', 'reason' => null],
            order: ['increment_id' => '1', 'created_at' => '2026-04-01T00:00:00Z', 'total' => '1.00'],
            items: [],
            refund: ['items' => '1.00', 'shipping' => '0.00', 'tax' => '0.00', 'total' => '1.00'],
            receipt: ['created_at' => '', 'confirmed_at' => '2026-04-02T09:05:00Z', 'locale' => 'en_US', 'ip_hash' => '', 'user_agent' => ''],
            merchant: ['name' => '', 'vat_id' => '', 'address' => ''],
            legal: ['withdrawal_period_days' => 14, 'article_ref' => 'Art. 9 CRD'],
        );
    }
}
