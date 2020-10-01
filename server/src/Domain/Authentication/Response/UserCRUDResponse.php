<?php declare(strict_types=1);

namespace App\Domain\Authentication\Response;

use App\Domain\Authentication\Entity\User;
use App\Domain\Common\Http;
use App\Domain\Common\Response\NormalResponse;

class UserCRUDResponse extends NormalResponse
{
    protected ?User $user = null;
    private int $exitCode;

    public static function createDeletedResponse(User $token): UserCRUDResponse
    {
        $response = new UserCRUDResponse();
        $response->status   = true;
        $response->exitCode = Http::HTTP_OK;
        $response->user    = $token;
        $response->message  = 'User was deleted';

        return $response;
    }

    public static function createTokenCreatedResponse(User $token): UserCRUDResponse
    {
        $response = new UserCRUDResponse();
        $response->status   = true;
        $response->exitCode = Http::HTTP_CREATED;
        $response->user    = $token;
        $response->message  = 'User created';

        return $response;
    }

    public static function createFoundResponse(User $token)
    {
        $response = new UserCRUDResponse();
        $response->status   = true;
        $response->exitCode = Http::HTTP_OK;
        $response->user    = $token;
        $response->message  = 'User found';

        return $response;
    }

    public static function createNotFoundResponse(): self
    {
        $response = new static();
        $response->status   = false;
        $response->exitCode = Http::HTTP_NOT_FOUND;
        $response->user     = null;
        $response->message  = 'User not found';

        return $response;
    }

    public function jsonSerialize(): array
    {
        $base = parent::jsonSerialize();
        $base['user'] = $this->user;

        return $base;
    }

    public function getTokenId(): ?string
    {
        return $this->user->getId();
    }

    public function getHttpCode(): int
    {
        return $this->exitCode;
    }
}
