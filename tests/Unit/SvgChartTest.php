<?php

namespace Tests\Unit;

use App\Support\SvgChart;
use PHPUnit\Framework\TestCase;

class SvgChartTest extends TestCase
{
    public function test_line_maps_values_into_the_box_and_closes_the_area(): void
    {
        $r = SvgChart::line([0, 50, 100], 200, 100, 10, 100);

        $this->assertSame([[10.0, 90.0], [100.0, 50.0], [190.0, 10.0]], $r['points']);
        $this->assertStringStartsWith('M10 90 C', $r['line']);
        $this->assertStringEndsWith('L190 90 L10 90 Z', $r['area']);
    }

    public function test_single_point_is_centred_and_empty_input_is_safe(): void
    {
        $this->assertSame([[100.0, 90.0]], SvgChart::line([0], 200, 100, 10, 100)['points']);
        $this->assertSame(['points' => [], 'line' => '', 'area' => ''], SvgChart::line([], 200, 100));
        $this->assertCount(3, SvgChart::line([0, 0, 0], 200, 100)['points'], 'all-zero data does not divide by zero');
    }

    public function test_donut_segments_cover_the_circle_in_order(): void
    {
        $d = SvgChart::donut([1, 1, 2], 10);

        $this->assertEqualsWithDelta(62.832, $d['circumference'], 0.001);
        $this->assertEqualsWithDelta(0.25, $d['segments'][0]['fraction'], 1e-9);
        $this->assertEqualsWithDelta(-15.708, $d['segments'][1]['offset'], 0.001);
        $this->assertEqualsWithDelta(31.416, $d['segments'][2]['dash'], 0.001);
    }

    public function test_donut_ignores_negatives_and_handles_all_zero(): void
    {
        $this->assertSame(0.0, SvgChart::donut([-5, 0], 10)['segments'][0]['fraction']);
        $this->assertSame(0.0, SvgChart::donut([0, 0], 10)['segments'][1]['dash']);
    }

    public function test_compact_labels_and_nice_max(): void
    {
        $this->assertSame('950', SvgChart::compact(950));
        $this->assertSame('1.2k', SvgChart::compact(1234));
        $this->assertSame('-3.4M', SvgChart::compact(-3_400_000));
        $this->assertSame(1.0, SvgChart::niceMax(0));
        $this->assertSame(10_000.0, SvgChart::niceMax(9_121.2));
        $this->assertSame(25_000.0, SvgChart::niceMax(21_000));
    }
}
