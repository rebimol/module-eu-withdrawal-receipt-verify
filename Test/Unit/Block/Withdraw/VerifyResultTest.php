<?php
/**
 * Copyright © MageMe. All rights reserved.
 * See LICENSE for license terms, or https://mageme.com/license.
 */
declare(strict_types=1);

namespace MageMe\EUWithdrawalReceiptVerify\Test\Unit\Block\Withdraw;

use MageMe\EUWithdrawalReceiptVerify\Block\Withdraw\VerifyResult;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class VerifyResultTest extends TestCase
{
    public function testSubmittedTimeIsShownInTheOrderStoreTimezoneWithTheZoneNamed(): void
    {
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('getConfigTimezone')
            ->with(ScopeInterface::SCOPE_STORE, 3)
            ->willReturn('America/Los_Angeles');
        $timezone->expects($this->once())
            ->method('formatDateTime')
            ->willReturnCallback(function (
                \DateTimeInterface $date,
                int $dateType,
                int $timeType,
                ?string $locale,
                ?string $zone,
            ): string {
                $this->assertSame('2026-10-20T19:34:00+00:00', $date->format(DATE_ATOM));
                $this->assertSame('America/Los_Angeles', $zone);
                return 'Oct 20, 2026, 12:34 PM';
            });

        $block = new VerifyResult(
            $this->createMock(Context::class),
            $timezone,
            $this->createMock(PriceCurrencyInterface::class),
            $this->createMock(HttpContext::class),
            ['created_at' => '2026-10-20T19:34:00Z', 'store_id' => 3],
        );

        $this->assertSame('Oct 20, 2026, 12:34 PM (America/Los_Angeles)', $block->getCreatedAtFormatted());
    }

    public function testReceiptIssuedWithTheOldReferenceShowsItAsFrozen(): void
    {
        self::assertSame('Art. 9 CRD', $this->blockWith(['article_ref' => 'Art. 9 CRD'])->getArticleRef());
    }

    public function testNewReceiptShowsArticle11a(): void
    {
        self::assertSame(
            'Art. 11a Directive 2011/83/EU',
            $this->blockWith(['article_ref' => 'Art. 11a Directive 2011/83/EU'])->getArticleRef(),
        );
    }

    public function testSignedInVisitorIsOfferedTheAccount(): void
    {
        self::assertTrue($this->blockWith([], $this->httpContext(true))->isCustomerLoggedIn());
    }

    public function testGuestIsNotOfferedTheAccount(): void
    {
        self::assertFalse($this->blockWith([], $this->httpContext(null))->isCustomerLoggedIn());
    }

    public function testUnparseableTimeIsReturnedAsStored(): void
    {
        $block = new VerifyResult(
            $this->createMock(Context::class),
            $this->createMock(TimezoneInterface::class),
            $this->createMock(PriceCurrencyInterface::class),
            $this->createMock(HttpContext::class),
            ['created_at' => 'not a date'],
        );

        $this->assertSame('not a date', $block->getCreatedAtFormatted());
    }

    private function blockWith(array $data, ?HttpContext $httpContext = null): VerifyResult
    {
        return new VerifyResult(
            $this->createMock(Context::class),
            $this->createMock(TimezoneInterface::class),
            $this->createMock(PriceCurrencyInterface::class),
            $httpContext ?? $this->createMock(HttpContext::class),
            $data,
        );
    }

    private function httpContext(?bool $auth): HttpContext
    {
        $context = $this->createMock(HttpContext::class);
        $context->method('getValue')->with(CustomerContext::CONTEXT_AUTH)->willReturn($auth);
        return $context;
    }
}
