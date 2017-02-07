<?php declare(strict_types=1);

namespace Manager;

use Doctrine\ORM\EntityManager;
use Factory\TokenFactory;
use Manager\Domain\TokenManagerInterface;
use Model\Entity\Token;
use Repository\Domain\TokenRepositoryInterface;

/**
 * @package Manager
 */
class TokenManager implements TokenManagerInterface
{
    /**
     * @var TokenRepositoryInterface $repository
     */
    private $repository;

    /**
     * @var string $adminToken
     */
    private $adminToken;

    /**
     * @var TokenFactory $factory
     */
    private $factory;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @param TokenRepositoryInterface $repository
     * @param TokenFactory $factory
     * @param EntityManager $entityManager
     * @param string $adminToken
     */
    public function __construct(
        TokenRepositoryInterface $repository,
        TokenFactory $factory,
        EntityManager $entityManager,
        string $adminToken
    ) {
        $this->repository    = $repository;
        $this->factory       = $factory;
        $this->entityManager = $entityManager;
        $this->adminToken    = $adminToken;
    }

    /**
     * @inheritdoc
     */
    public function isTokenValid(string $tokenId, string $roleName = ''): bool
    {
        if ($tokenId === $this->getAdminToken()) {
            return true;
        }

        $token = $this->repository->getTokenById($tokenId);

        if (!$token instanceof Token || !$roleName) {
            return false;
        }

        return $token->hasRole($roleName) && $token->isNotExpired();
    }

    /**
     * @inheritdoc
     */
    public function generateNewToken(array $roles, \DateTime $expires): Token
    {
        $token = $this->factory->createNewToken($roles, $expires);

        $this->entityManager->persist($token);
        $this->entityManager->flush($token);

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function removeToken(Token $token)
    {
        $this->entityManager->remove($token);
        $this->entityManager->flush($token);
    }

    /**
     * @return string
     */
    public function getAdminToken(): string
    {
        return $this->adminToken;
    }
}