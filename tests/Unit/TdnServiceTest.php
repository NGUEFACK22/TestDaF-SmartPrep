<?php

namespace Tests\Unit;

use App\Models\Result;
use App\Services\Exam\TdnService;
use Tests\TestCase;

class TdnServiceTest extends TestCase
{
    private TdnService $tdn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tdn = app(TdnService::class);
    }

    public function test_points20_conversion(): void
    {
        $this->assertSame(0.0, $this->tdn->points20(0.0));
        $this->assertSame(5.0, $this->tdn->points20(25.0));
        $this->assertSame(10.0, $this->tdn->points20(50.0));
        $this->assertSame(16.0, $this->tdn->points20(80.0));
        $this->assertSame(20.0, $this->tdn->points20(100.0));
    }

    public function test_points20_is_clamped_to_the_scale(): void
    {
        $this->assertSame(0.0, $this->tdn->points20(-20.0));
        $this->assertSame(20.0, $this->tdn->points20(140.0));
    }

    public function test_band_boundaries_follow_the_official_scale(): void
    {
        $cases = [
            [0.0, 'sous TDN 3'],
            [4.0, 'sous TDN 3'],
            [4.9, 'sous TDN 3'],
            [5.0, 'TDN 3'],
            [9.9, 'TDN 3'],
            [10.0, 'TDN 4'],
            [15.0, 'TDN 4'],
            [15.9, 'TDN 4'],
            [16.0, 'TDN 5'],
            [20.0, 'TDN 5'],
        ];

        foreach ($cases as [$points, $expected]) {
            $this->assertSame(
                $expected,
                $this->tdn->bandLabel($points),
                "La bande pour {$points} points devrait être « {$expected} »."
            );
        }
    }

    public function test_result_exposes_points20_and_tdn_accessors(): void
    {
        $result = new Result(['percentage' => 80.0]);

        $this->assertSame(16.0, (float) $result->points20);
        $this->assertSame('TDN 5', $result->tdn);

        $result = new Result(['percentage' => 60.0]);
        $this->assertSame(12.0, (float) $result->points20);
        $this->assertSame('TDN 4', $result->tdn);

        $result = new Result(['percentage' => 20.0]);
        $this->assertSame(4.0, (float) $result->points20);
        $this->assertSame('sous TDN 3', $result->tdn);

        $result = new Result(['percentage' => 0.0]);
        $this->assertSame(0.0, (float) $result->points20);
        $this->assertSame('sous TDN 3', $result->tdn);
    }

    public function test_c1_target_is_tdn5_on_16_to_20_points(): void
    {
        $target = $this->tdn->target();

        $this->assertSame(16, $target['min']);
        $this->assertSame(20, $target['max']);
        $this->assertStringContainsString('TDN 5', $target['label']);
    }
}