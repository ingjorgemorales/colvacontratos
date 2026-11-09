<?php

namespace Tests\Unit;

use App\Models\Contract;
use PHPUnit\Framework\TestCase;

final class ContractPaymentFieldsTest extends TestCase
{
    private function data(array $changes = []): array
    {
        return array_replace(['name' => 'SGC 010 2026', 'object' => 'Servicios', 'contract_month' => 'Septiembre'], $changes);
    }

    public function test_months_are_normalized_without_discarding_historical_values(): void
    {
        $this->assertSame('Septiembre', Contract::normalizeMonth(' septiembre '));
        $this->assertSame('Septiembre', Contract::normalizeMonth('09'));
        $this->assertSame('Diciembre', Contract::normalizeMonth('12'));
        $this->assertSame('Sept.', Contract::normalizeMonth('Sept.'));
        $this->assertNotEmpty(Contract::formErrors($this->data(['contract_month' => 'Sept.'])));
        $this->assertSame([], Contract::formErrors($this->data(['contract_month' => 'Sept.']), ['contract_month' => 'Sept.']));
        $this->assertNotEmpty(Contract::formErrors($this->data(['contract_month' => 'Otro']), ['contract_month' => 'Sept.']));
        $this->assertSame([], Contract::formErrors($this->data(), ['contract_month' => 'Sept.']));
    }

    public function test_payment_fields_are_optional_and_zero_is_a_valid_term(): void
    {
        $this->assertSame([], Contract::formErrors($this->data()));
        $this->assertSame([], Contract::formErrors($this->data(['payment_frequency' => '', 'payment_term_days' => ''])));
        foreach (['0', '30', '45', '60'] as $days) {
            $this->assertSame([], Contract::formErrors($this->data(['payment_frequency' => 'hitos', 'payment_term_days' => $days])));
        }
    }

    public function test_invalid_terms_and_frequencies_are_rejected(): void
    {
        foreach (['-1', '1.5', 'treinta', '65536', '1e2'] as $days) {
            $this->assertNotEmpty(Contract::formErrors($this->data(['payment_term_days' => $days])), $days);
        }
        $this->assertNotEmpty(Contract::formErrors($this->data(['payment_frequency' => 'inventada'])));
    }
}
