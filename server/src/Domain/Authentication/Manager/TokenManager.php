<?php declare(strict_types=1);

namespace App\Domain\Authentication\Manager;

use App\Domain\Authentication\Entity\Token;
use App\Domain\Authentication\Exception\InvalidTokenIdException;
use App\Domain\Authentication\Repository\TokenRepository;
use App\Domain\Authentication\Service\UuidValidator;
use Exception;
use Ramsey\Uuid\Uuid;

class TokenManager
{
    /**
     * @var TokenRepository
     */
    private $repository;

    /**
     * @var UuidValidator
     */
    private $uuidValidator;

    public function __construct(TokenRepository $repository, UuidValidator $uuidValidator)
    {
        $this->repository    = $repository;
        $this->uuidValidator = $uuidValidator;
    }

    /**
     * @param array $roles
     * @param \DateTimeImmutable $expirationTime
     * @param array $details
     *
     * @return Token
     * @throws Exception
     */
    public function generateNewToken(array $roles, \DateTimeImmutable $expirationTime,
                                     array $details, ?string $customId = null): Token
    {
        $token = new Token();
        $token->setId($customId ?: uniqid('', true));
        $token->setRoles($roles);
        $token->setExpirationDate($expirationTime);
        $token->setData($details);

        $this->repository->persist($token);

        if ($customId) {
            if (!$this->uuidValidator->isValid($customId)) {
                throw new InvalidTokenIdException('Invalid token id format. Expected UUIDv4 format');
            }

            $token->setId($customId);
            $this->repository->persist($token);
        }

        return $token;
    }

    public function revokeToken(Token $token): void
    {
        $this->repository->remove($token);
    }

    public function flushAll(): void
    {
        $this->repository->flush();
    }
}
