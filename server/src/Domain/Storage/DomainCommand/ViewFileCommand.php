<?php declare(strict_types=1);

namespace App\Domain\Storage\DomainCommand;

use App\Domain\Authentication\Repository\TokenRepository;
use App\Domain\Bus;
use App\Domain\Common\Service\Bus\CommandHandler;
use App\Domain\Storage\ActionHandler\ViewFileHandler;
use App\Domain\Storage\Context\CachingContext;
use App\Domain\Storage\Exception\AuthenticationException;
use App\Domain\Storage\Factory\Context\SecurityContextFactory;
use App\Domain\Storage\Form\ViewFileForm;

/**
 * Exposes ViewFileHandler via internal bus
 *
 * @see ViewFileHandler
 */
class ViewFileCommand implements CommandHandler
{
    private ViewFileHandler $handler;
    private SecurityContextFactory $authFactory;
    private TokenRepository $tokenRepository;

    public function __construct(ViewFileHandler $handler, SecurityContextFactory $authFactory, TokenRepository $repository)
    {
        $this->handler         = $handler;
        $this->authFactory     = $authFactory;
        $this->tokenRepository = $repository;
    }

    /**
     * Parameters in $input:
     *  - bool   isFileAlreadyValidated
     *  - string bytesRange
     *  - string filename
     *  - string password
     *  - Token  token
     *  - string ifNoneMatch
     *  - string ifModifiedSince
     *
     * @param mixed $input
     * @param string $path
     *
     * @return array|mixed
     * @throws AuthenticationException
     */
    public function handle($input, string $path)
    {
        $isFileAlreadyValidated = (bool) ($input['isFileAlreadyValidated'] ?? false);
        $token = $this->tokenRepository->findTokenById($input['token']);

        $form = new ViewFileForm();
        $form->bytesRange = $input['bytesRange'] ?? '';
        $form->filename   = $input['filename']   ?? '';
        $form->password   = $input['password']   ?? '';

        $securityContext = $this->authFactory->createViewingContextFromTokenAndForm($token, $form, $isFileAlreadyValidated);
        $cachingContext = new CachingContext(
            (string) ($input['ifNoneMatch'] ?? ''),
            ($input['ifModifiedSince'] ?? '') ?
                new \DateTimeImmutable($input['ifModifiedSince']) : null
        );

        $response = $this->handler->handle($form, $securityContext, $cachingContext);

        return ['response' => $response->jsonSerialize(), 'callback' => $response->getResponseCallback()];
    }

    public function supportsInput($input, string $path): bool
    {
        return true;
    }

    public function getSupportedPaths(): array
    {
        return [Bus::STORAGE_VIEW_FILE];
    }
}