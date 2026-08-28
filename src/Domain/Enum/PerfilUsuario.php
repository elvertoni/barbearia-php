<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Perfil (papel) de um usuário do sistema.
 * Valor alinhado com a coluna ENUM `usuarios.perfil`.
 *
 * - Dono: acesso total, inclusive gestão de usuários.
 * - Recepcao: operação do dia a dia (agenda, cadastros, fila).
 * - Barbeiro: enxerga e opera apenas a própria agenda.
 * - Cliente: portal self-service (telas no Módulo 2).
 */
enum PerfilUsuario: string
{
    case Dono = 'dono';
    case Recepcao = 'recepcao';
    case Barbeiro = 'barbeiro';
    case Cliente = 'cliente';

    public function label(): string
    {
        return match ($this) {
            self::Dono => 'Dono',
            self::Recepcao => 'Recepção',
            self::Barbeiro => 'Barbeiro',
            self::Cliente => 'Cliente',
        };
    }

    /**
     * Perfis que acessam o painel administrativo (tudo menos o cliente).
     */
    public function acessaPainel(): bool
    {
        return match ($this) {
            self::Dono, self::Recepcao, self::Barbeiro => true,
            self::Cliente => false,
        };
    }
}
