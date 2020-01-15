<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200111202444 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Set a default value for file_registry.timezone';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('UPDATE file_registry SET timezone = ? WHERE timezone = ""', [$this->getTimezone()]);
    }

    public function down(Schema $schema) : void
    {
    }

    private function getTimezone(): string
    {
        if (isset($_SERVER['TZ'])) {
            return $_SERVER['TZ'];
        }

        return \date_default_timezone_get();
    }
}
