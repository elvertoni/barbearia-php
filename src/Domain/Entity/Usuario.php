<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\PerfilUsuario;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Entidade Usuario — identidade + credencial + perfil.
 *
 * Imutável: propriedades readonly, factories criar()/reconstituir(), comId().
 * A senha nunca circula em texto puro nesta entidade — só o hash.
 */
final class Usuario
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nome,
        public readonly string $email,
        public readonly string $senhaHash,
        public readonly PerfilUsuario $perfil,
        public readonly bool $ativo,
        public readonly DateTimeImmutable $criadoEm,
    ) {
    }

    /**
     * Cria um usuário novo (sem id). Recebe o hash já calculado pelo use case.
     */
    public static function criar(string $nome, string $email, string $senhaHash, PerfilUsuario $perfil): self
    {
        return new self(
            id: null,
            nome: $nome,
            email: $email,
            senhaHash: $senhaHash,
            perfil: $perfil,
            ativo: true,
            criadoEm: new DateTimeImmutable('now', new DateTimeZone('UTC')),
        );
    }

    /**
     * Reconstrói a entidade a partir de dados do banco.
     */
    public static function reconstituir(
        int $id,
        string $nome,
        string $email,
        string $senhaHash,
        PerfilUsuario $perfil,
        bool $ativo,
        DateTimeImmutable $criadoEm,
    ): self {
        return new self(
            id: $id,
            nome: $nome,
            email: $email,
            senhaHash: $senhaHash,
            perfil: $perfil,
            ativo: $ativo,
            criadoEm: $criadoEm,
        );
    }

    public function comId(int $id): self
    {
        return new self(
            id: $id,
            nome: $this->nome,
            email: $this->email,
            senhaHash: $this->senhaHash,
            perfil: $this->perfil,
            ativo: $this->ativo,
            criadoEm: $this->criadoEm,
        );
    }

    /**
     * Verifica uma senha em texto puro contra o hash armazenado.
     */
    public function verificarSenha(string $senhaPlana): bool
    {
        return password_verify($senhaPlana, $this->senhaHash);
    }
}
