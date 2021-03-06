<?php declare(strict_types=1);

namespace App\Domain\Backup\Factory;

use App\Domain\Authentication\ValueObject\Permissions as UserRoles;
use App\Domain\Backup\Entity\Authentication\User;
use App\Domain\Backup\Entity\BackupCollection;
use App\Domain\Backup\Repository\UserAccessRepository;
use App\Domain\Backup\Security\CollectionManagementContext;
use App\Domain\Backup\Security\VersioningContext;
use App\Domain\PermissionsReference;

class SecurityContextFactory
{
    private UserAccessRepository $accessRepository;

    public function __construct(UserAccessRepository $accessRepository)
    {
        $this->accessRepository = $accessRepository;
    }

    public function createCollectionManagementContext(User $user, ?BackupCollection $collection): CollectionManagementContext
    {
        $roles = $this->getRolesIncludingContextOfACollection($collection, $user);

        return new CollectionManagementContext(
            $roles->has(PermissionsReference::PERMISSION_COLLECTION_ADD),
            $roles->has(PermissionsReference::PERMISSION_COLLECTION_CUSTOM_ID),
            $roles->has(PermissionsReference::PERMISSION_COLLECTION_ADD_WITH_INFINITE_LIMITS),
            $roles->has(PermissionsReference::PERMISSION_MODIFY_ALLOWED_COLLECTIONS),
            $roles->has(PermissionsReference::PERMISSION_COLLECTION_MODIFY_ANY_COLLECTION),
            $roles->has(PermissionsReference::PERMISSION_COLLECTION_VIEW_ANY_COLLECTION),
            $roles->has(PermissionsReference::PERMISSION_CAN_USE_LISTING_COLLECTION_ENDPOINT),
            $roles->has(PermissionsReference::PERMISSION_CAN_MANAGE_USERS_IN_ALLOWED_COLLECTIONS),
            $roles->has(PermissionsReference::PERMISSION_CAN_DELETE_ALLOWED_COLLECTIONS),
            $roles->has(PermissionsReference::PERMISSION_CAN_LIST_TOKENS_IN_COLLECTION),
            $roles->has(PermissionsReference::PERMISSION_ADMINISTRATOR),
            $user->getId(),
            $user
        );
    }

    public function createVersioningContext(User $user, BackupCollection $collection): VersioningContext
    {
        $roles = $this->getRolesIncludingContextOfACollection($collection, $user);

        return new VersioningContext(
            $roles->has(PermissionsReference::PERMISSION_COLLECTION_MODIFY_ANY_COLLECTION),
            $roles->has(PermissionsReference::PERMISSION_CAN_UPLOAD_TO_ALLOWED_COLLECTIONS),
            $roles->has(PermissionsReference::PERMISSION_LIST_VERSIONS_FOR_ALLOWED_COLLECTIONS),
            $roles->has(PermissionsReference::PERMISSION_DELETE_VERSIONS_IN_ALLOWED_COLLECTIONS),
            $roles->has(PermissionsReference::PERMISSION_FETCH_SINGLE_VERSION_FILE_IN_ALLOWED_COLLECTIONS),
            $roles->has(PermissionsReference::PERMISSION_ADMINISTRATOR),
            $user->getId()
        );
    }

    public function createShellContext(): CollectionManagementContext
    {
        return new CollectionManagementContext(
            true, true,
            true, true,
            true, true,
            true, true,
            true, true, true, null, null
        );
    }

    private function getRolesIncludingContextOfACollection(?BackupCollection $collection, User $user): UserRoles
    {
        $access = $collection ? $this->accessRepository->findForCollectionAndUser($collection, $user) : null;
        $roles  = UserRoles::createEmpty()->mergeWith($user->getRolesAsValueObject());

        if ($access) {
            $roles = $roles->mergeWith($access->getPermissions());
        }

        return $roles;
    }
}
