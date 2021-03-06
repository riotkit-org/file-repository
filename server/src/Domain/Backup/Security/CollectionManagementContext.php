<?php declare(strict_types=1);

namespace App\Domain\Backup\Security;

use App\Domain\Backup\Entity\Authentication\User;
use App\Domain\Backup\Entity\BackupCollection;
use App\Domain\Backup\Form\Collection\CreationForm;
use App\Domain\Backup\Form\Collection\DeleteForm;
use App\Domain\Backup\Form\Collection\EditForm;
use App\Domain\Backup\ValueObject\CollectionSpecificPermissions;

class CollectionManagementContext
{
    private bool $canCreateCollections;
    private bool $canAssignCustomIdInNewCollection;
    private bool $canCreateCollectionsWithoutLimit;
    private bool $canModifyAllowedCollections;
    private bool $canModifyAnyCollection;
    private bool $canUseListingEndpointToFindCollections;
    private bool $canAccessAnyCollection;
    private bool $canManageUsersInAllowedCollections;
    private bool $canDeleteAllowedCollections;
    private bool $canSeeTokensInCollection;
    private bool $isSystemAdmin;
    private ?string $userId;
    private ?User $user;

    public function __construct(
        bool $canCreateCollections,
        bool $canAssignCustomIdInNewCollection,
        bool $canCreateCollectionsWithoutLimit,
        bool $canModifyAllowedCollections,
        bool $canModifyAnyCollection,
        bool $canAccessAnyCollection,
        bool $canUseListingEndpoint,
        bool $canManageTokensInAllowedCollections,
        bool $canDeleteAllowedCollections,
        bool $canSeeTokensInCollection,
        bool $isSystemAdmin,
        ?string $userId,
        ?User $user
    ) {
        $this->canCreateCollections             = $canCreateCollections;
        $this->canAssignCustomIdInNewCollection = $canAssignCustomIdInNewCollection;
        $this->canCreateCollectionsWithoutLimit = $canCreateCollectionsWithoutLimit;
        $this->canModifyAllowedCollections      = $canModifyAllowedCollections;
        $this->canModifyAnyCollection           = $canModifyAnyCollection;
        $this->canAccessAnyCollection           = $canAccessAnyCollection;
        $this->canUseListingEndpointToFindCollections = $canUseListingEndpoint;
        $this->canManageUsersInAllowedCollections    = $canManageTokensInAllowedCollections;
        $this->canDeleteAllowedCollections            = $canDeleteAllowedCollections;
        $this->canSeeTokensInCollection         = $canSeeTokensInCollection;
        $this->isSystemAdmin                    = $isSystemAdmin;
        $this->userId                          = $userId;
        $this->user                             = $user;
    }

    public function canCreateCollection(CreationForm $form): bool
    {
        if (!$this->checkCollectionCanBeCreatedIfUnlimitedLimitsWereSet($form)) {
            return false;
        }

        return $this->canCreateCollections;
    }

    public function canCreateCollectionWithCustomId(CreationForm $form): bool
    {
        if (!$this->canCreateCollection($form)) {
            return false;
        }

        return $this->canAssignCustomIdInNewCollection;
    }

    private function checkCollectionCanBeCreatedIfUnlimitedLimitsWereSet(CreationForm $form): bool
    {
        if ($form->maxOneVersionSize && $form->maxCollectionSize && $form->maxBackupsCount) {
            return true;
        }

        return $this->canCreateCollectionsWithoutLimit;
    }

    public function canModifyCollection(EditForm $form): bool
    {
        if (!$form->collection) {
            return false;
        }

        if ($this->canModifyAnyCollection) {
            return true;
        }

        if (!$this->canModifyAllowedCollections) {
            return false;
        }

        return $this->isTokenAllowedFor($form->collection);
    }

    public function canDeleteCollection(DeleteForm $form): bool
    {
        if (!$form->collection) {
            return false;
        }

        if ($this->canModifyAnyCollection) {
            return true;
        }

        if (!$this->canDeleteAllowedCollections) {
            return false;
        }

        return $this->isTokenAllowedFor($form->collection);
    }

    public function canViewCollection(DeleteForm $form): bool
    {
        if (!$form->collection) {
            return false;
        }

        if ($this->canAccessAnyCollection) {
            return true;
        }

        return $this->isTokenAllowedFor($form->collection);
    }

    private function isTokenAllowedFor(BackupCollection $collection): bool
    {
        return $collection->isTokenIdAllowed($this->userId);
    }

    /**
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function hasTokenAttached(): bool
    {
        return $this->userId !== null;
    }

    public function canListMultipleCollections(): bool
    {
        return $this->canUseListingEndpointToFindCollections;
    }

    public function canSeeCollection(BackupCollection $collection): bool
    {
        if (!$this->canUseListingEndpointToFindCollections) {
            return false;
        }

        if ($this->canAccessAnyCollection) {
            return true;
        }

        return $collection->isTokenIdAllowed($this->userId);
    }

    public function canAssignThoseRolesToUsersInCollection(CollectionSpecificPermissions $roles): bool
    {
        if ($this->isSystemAdmin) {
            return true;
        }

        $allowed = $this->user->getPermissions();

        foreach ($roles->getAsList() as $role) {
            if (!in_array($role, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    public function canAddTokensToCollection(BackupCollection $collection): bool
    {
        if ($this->isSystemAdmin) {
            return true;
        }

        if (!$this->canManageUsersInAllowedCollections) {
            return false;
        }

        if ($this->canModifyAnyCollection) {
            return true;
        }

        return $collection->isTokenIdAllowed($this->userId);
    }

    public function canRevokeAccessToCollection(BackupCollection $collection): bool
    {
        if ($this->isSystemAdmin) {
            return true;
        }

        if (!$this->canManageUsersInAllowedCollections) {
            return false;
        }

        if ($this->canModifyAnyCollection) {
            return true;
        }

        return $collection->isTokenIdAllowed($this->userId);
    }

    public function canListCollectionTokens(BackupCollection $collection): bool
    {
        if ($this->isSystemAdmin) {
            return true;
        }

        if (!$this->canSeeTokensInCollection) {
            return false;
        }

        return $collection->isTokenIdAllowed($this->userId);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
