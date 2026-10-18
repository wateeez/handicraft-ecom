<?php

namespace App\Support;

/**
 * Pure geometry helpers for the server-rendered SVG charts. No I/O.
 */
final class SvgChart
{
    /**
     * Map values to points inside a w x h box and build smooth line/area paths.
     * All series that share an axis should be given the same $max.
     *
     * @param  array<int, float|int|string>  $values
     * @return array{points: array<int, array{0: float, 1: float}>, line: string, area: string}
     */
    public static function line(array $values, float $w, float $h, float $pad = 8.0, ?float $max = null, float $min = 0.0): array
    {
        $values = array_values(array_map('floatval', $values));
        $n = count($values);
        if ($n === 0) {
            return ['points' => [], 'line' => '', 'area' => ''];
        }

        $max ??= max($values);
        if ($max <= $min) {
            $max = $min + 1;
        }

        $points = [];
        foreach ($values as $i => $v) {
            $x = $n === 1 ? $w / 2 : $pad + $i * ($w - 2 * $pad) / ($n - 1);
            $y = $h - $pad - (($v - $min) / ($max - $min)) * ($h - 2 * $pad);
            $points[] = [round($x, 2), round($y, 2)];
        }

        $line = sprintf('M%s %s', $points[0][0], $points[0][1]);
        for ($i = 1; $i < $n; $i++) {
            $mid = round(($points[$i - 1][0] + $points[$i][0]) / 2, 2);
            $line .= sprintf(' C%s %s %s %s %s %s', $mid, $points[$i - 1][1], $mid, $points[$i][1], $points[$i][0], $points[$i][1]);
        }

        $baseline = round($h - $pad, 2);
        $area = $line . sprintf(' L%s %s L%s %s Z', $points[$n - 1][0], $baseline, $points[0][0], $baseline);

        return ['points' => $points, 'line' => $line, 'area' => $area];
    }

    /**
     * Stroke-dasharray geometry for a donut drawn with circles.
     *
     * @param  array<int, float|int|string>  $values  non-negative parts
     * @return array{circumference: float, segments: array<int, array{dash: float, gap: float, offset: float, fraction: float}>}
     */
    public static function donut(array $values, float $radius = 42.0): array
    {
        $values = array_map(fn ($v) => max(0.0, (float) $v), array_values($values));
        $total = array_sum($values);
        $c = round(2 * M_PI * $radius, 3);

        $segments = [];
        $consumed = 0.0;
        foreach ($values as $v) {
            $fraction = $total > 0 ? $v / $total : 0.0;
            $dash = round($fraction * $c, 3);
            $segments[] = [
                'dash' => $dash,
                'gap' => round($c - $dash, 3),
                'offset' => round(-$consumed * $c, 3),
                'fraction' => $fraction,
            ];
            $consumed += $fraction;
        }

        return ['circumference' => $c, 'segments' => $segments];
    }

    /**
     * Compact axis label: 950, 1.2k, 3.4M.
     */
    public static function compact(float $value): string
    {
        $abs = abs($value);
        $sign = $value < 0 ? '-' : '';

        return match (true) {
            $abs >= 1_000_000_000 => $sign . rtrim(rtrim(number_format($abs / 1_000_000_000, 1), '0'), '.') . 'B',
            $abs >= 1_000_000 => $sign . rtrim(rtrim(number_format($abs / 1_000_000, 1), '0'), '.') . 'M',
            $abs >= 1_000 => $sign . rtrim(rtrim(number_format($abs / 1_000, 1), '0'), '.') . 'k',
            default => $sign . rtrim(rtrim(number_format($abs, 1), '0'), '.'),
        };
    }

    /**
     * A "nice" axis ceiling so gridlines land on round numbers.
     */
    public static function niceMax(float $value): float
    {
        if ($value <= 0) {
            return 1.0;
        }

        $pow = 10 ** floor(log10($value));
        foreach ([1, 2, 2.5, 5, 10] as $step) {
            if ($value <= $step * $pow) {
                return (float) ($step * $pow);
            }
        }

        return (float) (10 * $pow);
    }
}
