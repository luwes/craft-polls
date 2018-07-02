<?php
/**
 * @link      https://wesleyluyten.com
 * @copyright Copyright (c) Wesley Luyten
 * @license   https://git.io/craft-polls-license
 */

namespace luwes\polls\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return true;
    }

    /**
     * Creates the tables.
     */
    public function createTables()
    {
        $this->createTable('{{%polls_polls}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'propagateQuestions' => $this->boolean()->defaultValue(true)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%polls_polls_sites}}', [
            'id' => $this->primaryKey(),
            'pollId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'hasUrls' => $this->boolean()->defaultValue(true)->notNull(),
            'uriFormat' => $this->text(),
            'template' => $this->string(500),
            'enabledByDefault' => $this->boolean()->defaultValue(true)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%polls_types}}', [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'hasTitleField' => $this->boolean()->defaultValue(true)->notNull(),
            'titleLabel' => $this->string()->defaultValue('Title'),
            'titleFormat' => $this->string(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%polls_questions}}', [
            'id' => $this->primaryKey(),
            'pollId' => $this->integer()->notNull(),
            'multipleOptions' => $this->boolean()->defaultValue(false)->notNull(),
            'multipleVotes' => $this->boolean()->defaultValue(false)->notNull(),
            'answerRequired' => $this->boolean()->defaultValue(true)->notNull(),
            'typeId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%polls_options}}', [
            'id' => $this->primaryKey(),
            'questionId' => $this->integer()->notNull(),
            'kind' => $this->enum('kind', ['defined', 'other'])->notNull()->defaultValue('defined'),
            'typeId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%polls_answers}}', [
            'id' => $this->primaryKey(),
            'questionId' => $this->integer()->notNull(),
            'optionId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'text' => $this->text(),
            'name' => $this->string(100),
            'email' => $this->string()->notNull(),
            'ipAddress' => $this->string(45),
            'userAgent' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    /**
     * Creates the indexes.
     */
    public function createIndexes()
    {
        $this->createIndex(null, '{{%polls_polls}}', ['handle'], true);
        $this->createIndex(null, '{{%polls_polls}}', ['name'], true);
        $this->createIndex(null, '{{%polls_polls_sites}}', ['pollId', 'siteId'], true);
        $this->createIndex(null, '{{%polls_polls_sites}}', ['siteId'], false);

        $this->createIndex(null, '{{%polls_types}}', ['name'], true);
        $this->createIndex(null, '{{%polls_types}}', ['handle'], true);
        $this->createIndex(null, '{{%polls_types}}', ['fieldLayoutId'], false);

        $this->createIndex(null, '{{%polls_questions}}', ['pollId'], false);
        $this->createIndex(null, '{{%polls_questions}}', ['typeId'], false);
        $this->createIndex(null, '{{%polls_questions}}', ['sortOrder'], false);

        $this->createIndex(null, '{{%polls_options}}', ['questionId'], false);
        $this->createIndex(null, '{{%polls_options}}', ['typeId'], false);
        $this->createIndex(null, '{{%polls_options}}', ['sortOrder'], false);

        $this->createIndex(null, '{{%polls_answers}}', ['questionId'], false);
        $this->createIndex(null, '{{%polls_answers}}', ['optionId'], false);
        $this->createIndex(null, '{{%polls_answers}}', ['userId'], false);
    }

    /**
     * Adds the foreign keys.
     */
    public function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%polls_polls_sites}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%polls_polls_sites}}', ['pollId'], '{{%polls_polls}}', ['id'], 'CASCADE', null);

        $this->addForeignKey(null, '{{%polls_types}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL', null);

        $this->addForeignKey(null, '{{%polls_questions}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%polls_questions}}', ['pollId'], '{{%polls_polls}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%polls_questions}}', ['typeId'], '{{%polls_types}}', ['id'], 'CASCADE', null);

        $this->addForeignKey(null, '{{%polls_options}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%polls_options}}', ['questionId'], '{{%polls_questions}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%polls_options}}', ['typeId'], '{{%polls_types}}', ['id'], 'CASCADE', null);

        $this->addForeignKey(null, '{{%polls_answers}}', ['questionId'], '{{%polls_questions}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%polls_answers}}', ['optionId'], '{{%polls_options}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%polls_answers}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
    }
}
