<?php declare(strict_types=1);

namespace App\Domain\Common\Exception;

use App\Domain\Errors;

/**
 * @codeCoverageIgnore
 */
class AuthenticationException extends ApplicationException
{
    /**
     * @return static
     */
    public static function fromFileReadAccessDenied()
    {
        return new static(
            Errors::ERR_MSG_REQUEST_READ_ACCESS_DENIED,
            Errors::ERR_REQUEST_READ_ACCESS_DENIED
        );
    }

    /**
     * @return static
     */
    public static function fromDeletionProhibited()
    {
        return new static(
            Errors::ERR_MSG_REQUEST_CANNOT_DELETE,
            Errors::ERR_REQUEST_CANNOT_DELETE
        );
    }

    /**
     * @return static
     */
    public static function fromEditProhibited()
    {
        return new static(
            Errors::ERR_MSG_PERMISSION_CANNOT_MODIFY,
            Errors::ERR_PERMISSION_CANNOT_MODIFY
        );
    }

    /**
     * @return static
     */
    public static function fromListingDenied()
    {
        return new static(
            Errors::ERR_MSG_LISTING_ENDPOINT_ACCESS_DENIED,
            Errors::ERR_LISTING_ENDPOINT_ACCESS_DENIED
        );
    }

    /**
     * @return static
     */
    public static function fromAccessDeniedToAssignCustomIds()
    {
        return new static(
            Errors::ERR_MSG_CANNOT_ASSIGN_CUSTOM_IDS,
            Errors::ERR_CANNOT_ASSIGN_CUSTOM_IDS
        );
    }

    /**
     * @return static
     */
    public static function fromCreationAccessDenied()
    {
        return new static(
            Errors::ERR_PERMISSION_MSG_CANNOT_CREATE,
            Errors::ERR_PERMISSION_CANNOT_CREATE
        );
    }

    /**
     * @return static
     */
    public static function fromPermissionDeniedToUseTechnicalEndpoints()
    {
        return new static(
            Errors::ERR_MSG_PERMISSION_NO_ACCESS_TO_TECHNICAL_ENDPOINTS,
            Errors::ERR_PERMISSION_NO_ACCESS_TO_TECHNICAL_ENDPOINTS
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'error' => $this->getMessage(),
            'code'  => $this->getCode(),
            'type'  => Errors::TYPE_AUTH_ERROR
        ];
    }

    public function getHttpCode(): int
    {
        return 403;
    }
}
