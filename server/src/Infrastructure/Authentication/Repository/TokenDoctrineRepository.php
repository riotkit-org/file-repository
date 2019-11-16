<?php declare(strict_types=1);

namespace App\Infrastructure\Authentication\Repository;

use App\Domain\Authentication\Entity\Token;
use App\Domain\Authentication\Exception\TokenAlreadyExistsException;
use App\Domain\Authentication\Repository\TokenRepository;
use App\Domain\Common\Repository\BaseRepository;
use App\Domain\Roles;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * @codeCoverageIgnore
 */
class TokenDoctrineRepository extends BaseRepository implements TokenRepository
{
    public function __construct(ManagerRegistry $registry, bool $readOnly)
    {
        parent::__construct($registry, Token::class, $readOnly);
    }

    public function persist(Token $token): void
    {
        $this->_em->persist($token);
    }

    public function flush(Token $token = null): void
    {
        try {
            $this->_em->flush($token);
        } catch (UniqueConstraintViolationException $exception) {
            throw new TokenAlreadyExistsException('Token of selected id already exists');
        }
    }

    public function findTokenById(string $id): ?Token
    {
        if (Roles::isTestToken($id)) {
            $token = new Token();
            $token->setId(Roles::TEST_TOKEN);
            $token->setRoles([Roles::ROLE_ADMINISTRATOR]);

            return $token;
        }

        return $this->find($id);
    }

    public function remove(Token $token): void
    {
        $this->_em->remove($token);
    }

    public function deactivate(Token $token): void
    {
        $token->deactivate();
        $this->persist($token);
    }

    public function getExpiredTokens(): array
    {
        $qb = $this->createQueryBuilder('token');
        $qb->where('token.expirationDate <= :now')
            ->setParameter('now', (new \DateTime())->format('Y-m-d H:i:s'));

        return $qb->getQuery()->getResult();
    }
}
