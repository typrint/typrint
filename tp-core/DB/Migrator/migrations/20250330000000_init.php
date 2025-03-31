<?php

declare(strict_types=1);

/*
 * This file is part of TyPrint.
 *
 * (c) TyPrint Core Team <https://typrint.org>
 *
 * This source file is subject to the GNU General Public License version 3
 * that is with this source code in the file LICENSE.
 */

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Migration\AbstractMigration;

class Init extends AbstractMigration
{
    public function change(): void
    {
        $this->table(DB_TABLE_PREFIX.'users', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'generated' => PostgresAdapter::GENERATED_BY_DEFAULT])
            ->addColumn('name', 'string', ['limit' => 64])
            ->addColumn('display_name', 'string', ['limit' => 255])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('url', 'string', ['limit' => 255])
            ->addColumn('status', 'string', ['limit' => 20])
            ->addTimestamps()
            ->addIndex('name', ['unique' => true, 'name' => 'uk_'.DB_TABLE_PREFIX.'users_name'])
            ->addIndex('email', ['unique' => true, 'name' => 'uk_'.DB_TABLE_PREFIX.'users_email'])
            ->addIndex('display_name', ['name' => 'idx_'.DB_TABLE_PREFIX.'users_display_name'])
            ->addIndex('status', ['name' => 'idx_'.DB_TABLE_PREFIX.'users_status'])
            ->create();

        $this->table(DB_TABLE_PREFIX.'usermeta', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'generated' => PostgresAdapter::GENERATED_BY_DEFAULT])
            ->addColumn('user_id', 'biginteger', ['signed' => false])
            ->addColumn('key', 'string', ['limit' => 255])
            ->addColumn('value', 'text', ['limit' => MysqlAdapter::TEXT_LONG])
            ->addIndex(['user_id', 'key'], ['limit' => ['value' => 32], 'name' => 'idx_'.DB_TABLE_PREFIX.'usermeta_user_id_key'])
            ->addIndex(['key', 'value', 'user_id'], ['limit' => ['value' => 32], 'name' => 'idx_'.DB_TABLE_PREFIX.'usermeta_key_value_user_id'])
            ->create();

        $this->table(DB_TABLE_PREFIX.'userroles', ['id' => false, 'primary_key' => ['user_id', 'role']])
            ->addColumn('user_id', 'biginteger', ['signed' => false])
            ->addColumn('role', 'string', ['limit' => 32])
            ->addIndex('role', ['name' => 'idx_'.DB_TABLE_PREFIX.'userroles_role'])
            ->create();

        $this->table(DB_TABLE_PREFIX.'options', ['id' => false, 'primary_key' => ['name']])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('value', 'text', ['limit' => MysqlAdapter::TEXT_LONG])
            ->addColumn('autoload', 'boolean', ['default' => true])
            ->addIndex('autoload', ['name' => 'idx_'.DB_TABLE_PREFIX.'options_autoload'])
            ->create();

        $this->table(DB_TABLE_PREFIX.'contents', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'generated' => PostgresAdapter::GENERATED_BY_DEFAULT])
            ->addColumn('parent', 'biginteger', ['signed' => false, 'default' => 0])
            ->addColumn('user_id', 'biginteger', ['signed' => false])
            ->addColumn('type', 'string', ['limit' => 32])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('title', 'text')
            ->addColumn('excerpt', 'text')
            ->addColumn('content', 'text', ['limit' => MysqlAdapter::TEXT_LONG])
            ->addColumn('status', 'string', ['limit' => 20])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addTimestamps()
            ->addIndex(['name', 'type'], ['unique' => true, 'name' => 'uk_'.DB_TABLE_PREFIX.'contents_name_type'])
            ->addIndex(['parent', 'type', 'status'], ['name' => 'idx_'.DB_TABLE_PREFIX.'contents_parent_type_status'])
            ->addIndex(['type', 'status', 'created_at', 'user_id'], ['name' => 'idx_'.DB_TABLE_PREFIX.'contents_type_status_created_at_user_id'])
            ->addIndex(['user_id', 'type', 'status', 'created_at'], ['name' => 'idx_'.DB_TABLE_PREFIX.'contents_user_id_type_status_created_at'])
            ->create();

        $this->table(DB_TABLE_PREFIX.'contentmeta', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'generated' => PostgresAdapter::GENERATED_BY_DEFAULT])
            ->addColumn('content_id', 'biginteger', ['signed' => false])
            ->addColumn('key', 'string', ['limit' => 255])
            ->addColumn('value', 'text', ['limit' => MysqlAdapter::TEXT_LONG])
            ->addIndex(['content_id', 'key'], ['limit' => ['value' => 32], 'name' => 'idx_'.DB_TABLE_PREFIX.'contentmeta_content_id_key'])
            ->addIndex(['key', 'value', 'content_id'], ['limit' => ['value' => 32], 'name' => 'idx_'.DB_TABLE_PREFIX.'contentmeta_key_value_content_id'])
            ->create();

        $this->table(DB_TABLE_PREFIX.'terms', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'generated' => PostgresAdapter::GENERATED_BY_DEFAULT])
            ->addColumn('slug', 'string', ['limit' => 200])
            ->addColumn('name', 'string', ['limit' => 200])
            ->addIndex('slug', ['name' => 'idx_'.DB_TABLE_PREFIX.'terms_slug'])
            ->addIndex('name', ['name' => 'idx_'.DB_TABLE_PREFIX.'terms_name'])
            ->create();

        $this->table(DB_TABLE_PREFIX.'termmeta', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'generated' => PostgresAdapter::GENERATED_BY_DEFAULT])
            ->addColumn('term_id', 'biginteger', ['signed' => false])
            ->addColumn('key', 'string', ['limit' => 255])
            ->addColumn('value', 'text', ['limit' => MysqlAdapter::TEXT_LONG])
            ->addIndex(['term_id', 'key'], ['limit' => ['value' => 32], 'name' => 'idx_'.DB_TABLE_PREFIX.'termmeta_term_id_key'])
            ->addIndex(['key', 'value', 'term_id'], ['limit' => ['value' => 32], 'name' => 'idx_'.DB_TABLE_PREFIX.'termmeta_key_value_term_id'])
            ->create();

        $this->table(DB_TABLE_PREFIX.'term_taxonomy', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'generated' => PostgresAdapter::GENERATED_BY_DEFAULT])
            ->addColumn('term_id', 'biginteger', ['signed' => false])
            ->addColumn('taxonomy', 'string', ['limit' => 32])
            ->addColumn('description', 'text', ['limit' => MysqlAdapter::TEXT_LONG])
            ->addColumn('count', 'biginteger', ['default' => 0])
            ->addIndex('taxonomy', ['name' => 'idx_'.DB_TABLE_PREFIX.'term_taxonomy_taxonomy'])
            ->addIndex(['term_id', 'taxonomy'], ['unique' => true, 'name' => 'uk_'.DB_TABLE_PREFIX.'term_taxonomy_term_id_taxonomy'])
            ->create();

        $this->table(DB_TABLE_PREFIX.'term_relationships', ['id' => false, 'primary_key' => ['content_id', 'term_taxonomy_id']])
            ->addColumn('content_id', 'biginteger', ['signed' => false])
            ->addColumn('term_taxonomy_id', 'biginteger', ['signed' => false])
            ->addIndex('term_taxonomy_id', ['name' => 'idx_'.DB_TABLE_PREFIX.'term_relationships_term_taxonomy_id'])
            ->create();
    }
}
