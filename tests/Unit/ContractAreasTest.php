<?php

namespace Tests\Unit;

use App\Models\Contract;
use PHPUnit\Framework\TestCase;

final class ContractAreasTest extends TestCase
{
    private const AREAS = [['id' => 1], ['id' => 2], ['id' => 3]];
    private const CHILDREN = [['id' => 10, 'area_id' => 2], ['id' => 11, 'area_id' => 2], ['id' => 20, 'area_id' => 3]];

    public function test_area_without_children_accepts_null_but_not_a_foreign_child(): void
    {
        $this->assertSame([], $this->errors(1, ''));
        $this->assertNotEmpty($this->errors(1, 10));
        $this->assertSame([], $this->errors('', ''));
        $this->assertNotEmpty($this->errors('', 10));
        $this->assertNotEmpty($this->errors(999, ''));
    }

    public function test_area_with_children_requires_one_of_its_own_children(): void
    {
        $this->assertSame([], $this->errors(2, 10));
        $this->assertSame([], $this->errors(2, 11));
        $this->assertNotEmpty($this->errors(2, ''));
        $this->assertNotEmpty($this->errors(2, 20));
        $this->assertNotEmpty($this->errors(2, 999));
    }

    public function test_historical_pair_is_preserved_but_cannot_be_assigned_to_another_area(): void
    {
        $old = ['area_id' => 90, 'sub_area_id' => 91];
        $this->assertSame([], Contract::areaErrors($old, self::AREAS, self::CHILDREN, $old));
        $this->assertNotEmpty(Contract::areaErrors(['area_id' => 2, 'sub_area_id' => 91], self::AREAS, self::CHILDREN, $old));
        $this->assertNotEmpty(Contract::areaErrors($old, self::AREAS, self::CHILDREN));
        $missing = ['area_id' => 2, 'sub_area_id' => null];
        $this->assertSame([], Contract::areaErrors($missing, self::AREAS, self::CHILDREN, $missing));
    }

    private function errors($area, $child): array
    {
        return Contract::areaErrors(['area_id' => $area, 'sub_area_id' => $child], self::AREAS, self::CHILDREN);
    }
}
