<?php
/**
 * Copyright © MageMe. All rights reserved.
 * See LICENSE for license terms, or https://mageme.com/license.
 */
declare(strict_types=1);

namespace MageMe\EUWithdrawalReceiptVerify\Test\Unit\Model\Receipt;

use MageMe\EUWithdrawalReceiptVerify\Model\Receipt\ReceiptCanonicalizer;
use MageMe\EUWithdrawal\Model\Receipt\ReceiptDto;
use PHPUnit\Framework\TestCase;

class ReceiptCanonicalizerTest extends TestCase
{
    private ReceiptCanonicalizer $c;

    protected function setUp(): void
    {
        $this->c = new ReceiptCanonicalizer();
    }

    public function testKeyOrderInvariance(): void
    {
        $a = $this->makeDto(['name' => 'A', 'email' => 'a@x']);
        $b = $this->makeDto(['email' => 'a@x', 'name' => 'A']);
        $this->assertSame($this->c->canonicalize($a), $this->c->canonicalize($b));
    }

    public function testNfcNormalization(): void
    {
        $composed   = $this->makeDto(['name' => "\u{00E9}", 'email' => 'x@y']); // é
        $decomposed = $this->makeDto(['name' => "e\u{0301}", 'email' => 'x@y']); // e + combining acute
        $this->assertSame($this->c->canonicalize($composed), $this->c->canonicalize($decomposed));
    }

    public function testZeroFractionPreserved(): void
    {
        $dto = $this->makeDto(['name' => 'A', 'email' => 'a@x'], refundTotal: '3.00');
        $out = $this->c->canonicalize($dto);
        $this->assertStringContainsString('"3.00"', $out);
    }

    public function testUnicodeRoundTrip(): void
    {
        $dto = $this->makeDto(['name' => 'Müller & 文字', 'email' => 'x@y']);
        $out = $this->c->canonicalize($dto);
        $this->assertStringContainsString('Müller', $out);
        $this->assertStringContainsString('文字', $out);
    }

    public function testNestedArraySort(): void
    {
        $dto = $this->makeDto(['name' => 'A', 'email' => 'a@x']);
        $out = $this->c->canonicalize($dto);
        $posConsumer = strpos($out, '"consumer"');
        $posEmail    = strpos($out, '"email"');
        $posName     = strpos($out, '"name"');
        $this->assertTrue($posConsumer !== false && $posEmail > $posConsumer && $posEmail < $posName);
    }

    public function testDifferentInputProducesDifferentOutput(): void
    {
        $a = $this->makeDto(['name' => 'Alice', 'email' => 'a@x']);
        $b = $this->makeDto(['name' => 'Bob',   'email' => 'a@x']);
        $this->assertNotSame($this->c->canonicalize($a), $this->c->canonicalize($b));
    }

    public function testByteIdenticalForIdenticalInput(): void
    {
        $a = $this->makeDto(['name' => 'A', 'email' => 'a@x']);
        $b = $this->makeDto(['name' => 'A', 'email' => 'a@x']);
        $this->assertSame($this->c->canonicalize($a), $this->c->canonicalize($b));
    }

    public function testJsonEscapeFlagsCorrect(): void
    {
        $dto = $this->makeDto(['name' => 'a/b', 'email' => 'x@y']);
        $out = $this->c->canonicalize($dto);
        $this->assertStringContainsString('"a/b"', $out); // JSON_UNESCAPED_SLASHES
        $this->assertStringNotContainsString('\\/', $out);
    }

    public function testSnapshotRoundTripIsHashStable(): void
    {
        $original = $this->makeDto(['name' => 'Müller & 文字', 'email' => 'x@y']);

        // Mirror RequestCreator (store toArray() as JSON) + ReceiptBuilder::fromSnapshot
        // (decode JSON, rebuild DTO). The canonical bytes must be byte-identical, so the
        // stored content_hash still verifies after the snapshot replaces live config/order.
        $json = json_encode(
            $original->toArray(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        $d = json_decode($json, true);
        $rebuilt = new ReceiptDto(
            requestId: $original->requestId,
            consumer: $d[ReceiptDto::CONSUMER],
            order: $d[ReceiptDto::ORDER],
            items: $d[ReceiptDto::ITEMS],
            refund: $d[ReceiptDto::REFUND],
            receipt: $d[ReceiptDto::RECEIPT],
            merchant: $d[ReceiptDto::MERCHANT],
            legal: $d[ReceiptDto::LEGAL],
        );

        $this->assertSame($this->c->canonicalize($original), $this->c->canonicalize($rebuilt));
    }

    private function makeDto(array $consumer, string $refundTotal = '10.00'): ReceiptDto
    {
        return new ReceiptDto(
            requestId: 1,
            consumer: $consumer + ['reason' => null],
            order: ['increment_id' => '000000001', 'created_at' => '2026-04-01T10:00:00Z', 'total' => '50.00'],
            items: [['order_item_id' => 1, 'sku' => 'X', 'qty' => 1, 'refund_amount' => '10.00']],
            refund: ['items' => '10.00', 'shipping' => '0.00', 'tax' => '0.00', 'total' => $refundTotal],
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
