<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enum;

use App\Domain\Enum\DiaSemana;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class DiaSemanaTest extends TestCase
{
    public function testFromDateTimeSegunda(): void
    {
        // 2025-01-06 é uma segunda-feira
        $date = new DateTimeImmutable('2025-01-06', new DateTimeZone('UTC'));
        $this->assertSame(DiaSemana::Segunda, DiaSemana::fromDateTime($date));
    }

    public function testFromDateTimeDomingo(): void
    {
        // 2025-01-05 é um domingo
        $date = new DateTimeImmutable('2025-01-05', new DateTimeZone('UTC'));
        $this->assertSame(DiaSemana::Domingo, DiaSemana::fromDateTime($date));
    }

    public function testTodosOsDiasTemLabel(): void
    {
        foreach (DiaSemana::cases() as $dia) {
            $this->assertNotEmpty($dia->label());
        }
    }
}
