<?php

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200807120319 extends AbstractPimcoreMigration
{
    public function preUp(Schema $schema)
    {
        parent::preUp($schema);
    }


    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $schema
            ->createTable('testtable')
            ->addColumn('col1', 'integer')
        ;
        // this up() migration is auto-generated, please modify it to your needs

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
