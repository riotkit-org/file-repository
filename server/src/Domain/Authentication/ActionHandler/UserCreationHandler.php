<?php declare(strict_types=1);

namespace App\Domain\Authentication\ActionHandler;

use App\Domain\Authentication\Exception\AuthenticationException;
use App\Domain\Authentication\Exception\InvalidUserIdException;
use App\Domain\Authentication\Exception\UserAlreadyExistsException;
use App\Domain\Authentication\Form\AuthForm;
use App\Domain\Authentication\Manager\UserManager;
use App\Domain\Authentication\Response\UserCRUDResponse;
use App\Domain\Authentication\Security\Context\AuthenticationManagementContext;
use App\Domain\Common\Exception\DomainAssertionFailure;
use Exception;

class UserCreationHandler
{
    private UserManager $tokenManager;

    /**
     * @var string A modificator eg. "+30 minutes"
     */
    private string $expirationTimeModifier;

    public function __construct(UserManager $manager, string $expirationTime)
    {
        $this->tokenManager           = $manager;
        $this->expirationTimeModifier = $expirationTime;
    }

    /**
     * @param AuthForm $form
     * @param AuthenticationManagementContext $context
     *
     * @return UserCRUDResponse
     *
     * @throws DomainAssertionFailure
     * @throws Exception
     */
    public function handle(AuthForm $form, AuthenticationManagementContext $context): UserCRUDResponse
    {
        $this->assertHasRights($context, $form);

        try {
            $token = $this->tokenManager->generateNewToken(
                $form->roles,
                $form->expires,
                $form->data->toArray(),
                $form->email,
                $form->password,
                $form->organization,
                $form->about,
                $form->id
            );
        }
        catch (InvalidUserIdException $exception) {
            throw DomainAssertionFailure::fromErrors([$exception]);
        }

        try {
            $this->tokenManager->flushAll();

        } catch (UserAlreadyExistsException $exception) {
            throw DomainAssertionFailure::fromErrors([$exception]);
        }

        return UserCRUDResponse::createTokenCreatedResponse($token);
    }

    /**
     * @param AuthenticationManagementContext $context
     *
     * @throws AuthenticationException
     */
    private function assertHasRights(AuthenticationManagementContext $context, AuthForm $form): void
    {
        if (!$context->canGenerateNewToken()) {
            throw new AuthenticationException(
                'Current access does not allow to create users',
                AuthenticationException::CODES['not_authenticated']
            );
        }

        if ($form->id && !$context->canCreateTokensWithPredictableIdentifiers()) {
            throw new AuthenticationException(
                'Current access does not allow setting predictable identifiers for users',
                AuthenticationException::CODES['no_permissions_for_predictable_ids']
            );
        }
    }
}
