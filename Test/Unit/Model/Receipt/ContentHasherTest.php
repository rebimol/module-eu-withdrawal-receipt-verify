<?php
declare(strict_types=1);

namespace MageMe\EUWithdrawalReceiptVerify\Test\Unit\Model\Receipt;

use MageMe\EUWithdrawal\Model\Receipt\ReceiptDto;
use MageMe\EUWithdrawalReceiptVerify\Model\Receipt\ContentHasher;
use MageMe\EUWithdrawalReceiptVerify\Model\Receipt\ReceiptCanonicalizer;
use PHPUnit\Framework\TestCase;

class ContentHasherTest extends TestCase
{
    public function testReturns64HexChars(): void
    {
        $h = (new ContentHasher(new ReceiptCanonicalizer()))->hash($this->dto('A'));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $h);
    }

    public function testDeterministic(): void
    {
        $hasher = new ContentHasher(new ReceiptCanonicalizer());
        $this->assertSame($hasher->hash($this->dto('A')), $hasher->hash($this->dto('A')));
    }

    public function testOneBitChangeDifferentHash(): void
    {
        $hasher = new ContentHasher(new ReceiptCanonicalizer());
        $this->assertNotSame($hasher->hash($this->dto('A')), $hasher->hash($this->dto('B')));
    }

    private function dto(string $name): ReceiptDto
    {
        return new ReceiptDto(
            requestId: 1,
            consumer: ['name' => $name, 'email' => 'x@y', 'reason' => null],
            order: ['increment_id' => '000000001', 'created_at' => '2026-04-01T10:00:00Z', 'total' => '50.00'],
            items: [['order_item_id' => 1, 'sku' => 'X', 'qty' => 1, 'refund_amount' => '10.00']],
            refund: ['items' => '10.00', 'shipping' => '0.00', 'tax' => '0.00', 'total' => '10.00'],
            receipt: [
                'created_at' => '2026-04-02T09:00:00Z',
                'confirmed_at' => '2026-04-02T09:05:00Z',
                'locale' => 'en_US',
                'ip_hash' => 'abc',
                'user_agent' => 'UA',
            ],
            merchant: ['name' => 'Shop', 'vat_id' => 'DE123', 'address' => 'Berlin'],
            legal: ['withdrawal_period_days' => 14, 'article_ref' => 'Art. 9 CRD'],
        );
    }
}
