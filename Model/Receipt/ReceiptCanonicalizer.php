<?php
declare(strict_types=1);

namespace MageMe\EUWithdrawalReceiptVerify\Model\Receipt;

use MageMe\EUWithdrawal\Model\Receipt\ReceiptDto;
use Normalizer;

class ReceiptCanonicalizer
{
    /**
     * Canonicalize.
     *
     * @param ReceiptDto $dto
     * @return string
     */
    public function canonicalize(ReceiptDto $dto): string
    {
        $array = $this->normalize($dto->toArray());
        $json  = json_encode(
            $array,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        );
        return $json;
    }

    /**
     * Normalize.
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalize(mixed $value): mixed
    {
        if (is_string($value)) {
            return Normalizer::normalize($value, Normalizer::FORM_C) ?: $value;
        }
        if (is_array($value)) {
            $isList = array_is_list($value);
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->normalize($v);
            }
            if (!$isList) {
                ksort($out, SORT_STRING);
            }
            return $out;
        }
        return $value;
    }
}
