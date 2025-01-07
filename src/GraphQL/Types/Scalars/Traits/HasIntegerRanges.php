<?php

namespace Enjin\Platform\GraphQL\Types\Scalars\Traits;

use phpseclib3\Math\BigInteger;

trait HasIntegerRanges
{
    public function isIntegerRange(string $value) : bool
    {
        return str_contains($value, '..');
    }

    public static function expandRanges($values): array
    {
        return collect($values)
            ->flatten()
            ->map(function ($range) {
                if (preg_match('/-?[0-9]+(\.\.)-?[0-9]+/', $range)) {
                    [$start, $end] = explode('..', $range, 2);
                    $range = [];
                    while ($start <= $end) {
                        $range[] = $start;
                        $start = bcadd($start, '1');
                    }
                }

                return $range;
            })
            ->flatten()
            ->transform(fn ($val) => (string) $val)
            ->unique()
            ->sort()
            ->all();
    }

    protected function serializeValue($value): array
    {
        sort($value, SORT_NUMERIC);
        $arrayCount = count($value);
        $result = [];

        for ($i = 0; $i < $arrayCount; $i++) {
            $currentValue = new BigInteger($value[$i]);
            if ($i + 1 != $arrayCount) {
                $nextValue = new BigInteger($value[$i + 1]);
            }

            if (empty($start) && ($i + 1 != $arrayCount && $nextValue->equals($currentValue->add(new BigInteger(1))))) {
                $start = $currentValue->toString();
                $curRange = $start . '..';

                continue;
            }

            if (!empty($start) && ($i + 1 == $arrayCount || !$nextValue->equals($currentValue->add(new BigInteger(1))))) {
                $start = null;
                $curRange .= $currentValue->toString();
                $result[] = $curRange;

                continue;
            }

            if (empty($start)) {
                $result[] = $currentValue->toString();
            }
        }

        return $result;
    }

    protected function validateValue($range): bool
    {
        if (preg_match('/-?[0-9]+(\.\.)-?[0-9]+/', (string) $range)) {
            [$start, $end] = explode('..', (string) $range, 2);
            if (!is_numeric($start) || !is_numeric($end)) {
                return true;
            }

            $start = new BigInteger($start);
            $end = new BigInteger($end);
            if ($start->compare($end) > 0) {
                return true;
            }

            return false;
        }

        if (!is_numeric($range) || preg_match('/[^-0-9]/', $range)) {
            return true;
        }

        return false;
    }
}
