<?php declare(strict_types=1);

namespace App\Domain\Storage\Factory;

use App\Domain\Authentication\Entity\User;
use App\Domain\Common\ValueObject\Password;
use App\Domain\Storage\Entity\StoredFile;
use App\Domain\Storage\Form\UploadForm;
use App\Domain\Storage\Repository\TagRepository;
use App\Domain\Storage\ValueObject\Filename;

/**
 * Factory - Produces complete StoredFile objects basing on UploadForm
 *           The resulting objects are ready to persist by the persistence layer.
 */
class StoredFileFactory
{
    private TagRepository $tagRepository;

    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * @param UploadForm $form
     * @param Filename $filename
     * @param User $token
     *
     * @return StoredFile
     *
     * @throws InvalidAttributeException
     */
    public function createFromForm(UploadForm $form, Filename $filename, User $token): StoredFile
    {
        $storedFile = StoredFile::newFromFilename($filename, $token->getId());
        $this->mapFromForm($form, $storedFile);

        return $storedFile;
    }

    /**
     * @param UploadForm $form
     * @param StoredFile $storedFile
     *
     * @return StoredFile
     */
    public function mapFromForm(UploadForm $form, StoredFile $storedFile): StoredFile
    {
        // fields
        $form->password instanceof Password
            ? $storedFile->replaceEncodedPassword($form->password)
            : $storedFile->changePassword($form->password);

        $storedFile->setPublic($form->public);

        // related tags
        $tags = $this->tagRepository->findOrCreateTagsByNames($form->tags);

        foreach ($tags as $tag) {
            $storedFile->addTag($tag);
        }

        return $storedFile;
    }
}
