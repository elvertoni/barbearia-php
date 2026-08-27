<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Máquina de estados do agendamento (Regra 6 do PRD).
 *
 * Transições permitidas:
 *   solicitado → confirmado → em_atendimento → concluido
 *   solicitado → cancelado
 *   confirmado → cancelado
 *   confirmado → no_show
 */
enum StatusAgendamento: string
{
    case Solicitado = 'solicitado';
    case Confirmado = 'confirmado';
    case EmAtendimento = 'em_atendimento';
    case Concluido = 'concluido';
    case Cancelado = 'cancelado';
    case NoShow = 'no_show';

    /**
     * Retorna os status para os quais é possível transitar a partir do status atual.
     *
     * @return self[]
     */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::Solicitado => [self::Confirmado, self::Cancelado],
            self::Confirmado => [self::EmAtendimento, self::Cancelado, self::NoShow],
            self::EmAtendimento => [self::Concluido],
            self::Concluido, self::Cancelado, self::NoShow => [],
        };
    }

    /**
     * Verifica se a transição para o status destino é permitida.
     */
    public function podeTransitarPara(self $destino): bool
    {
        return in_array($destino, $this->transicoesPermitidas(), true);
    }

    /**
     * Retorna true se este status representa um agendamento "ativo"
     * (ou seja, que ocupa slot e deve ser considerado na query de conflito).
     */
    public function ocupaSlot(): bool
    {
        return match ($this) {
            self::Solicitado, self::Confirmado, self::EmAtendimento => true,
            self::Concluido, self::Cancelado, self::NoShow => false,
        };
    }
}
