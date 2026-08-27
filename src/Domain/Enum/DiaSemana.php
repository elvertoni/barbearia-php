<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Dias da semana com valor inteiro (1=segunda a 7=domingo).
 * Alinhado com ISO-8601 e com a coluna dia_semana do banco.
 */
enum DiaSemana: int
{
    case Segunda = 1;
    case Terca = 2;
    case Quarta = 3;
    case Quinta = 4;
    case Sexta = 5;
    case Sabado = 6;
    case Domingo = 7;

    /**
     * Cria a partir de um DateTimeInterface (usa formato ISO 'N': 1=seg, 7=dom).
     */
    public static function fromDateTime(\DateTimeInterface $date): self
    {
        return self::from((int) $date->format('N'));
    }

    public function label(): string
    {
        return match ($this) {
            self::Segunda => 'Segunda-feira',
            self::Terca => 'Terça-feira',
            self::Quarta => 'Quarta-feira',
            self::Quinta => 'Quinta-feira',
            self::Sexta => 'Sexta-feira',
            self::Sabado => 'Sábado',
            self::Domingo => 'Domingo',
        };
    }
}
