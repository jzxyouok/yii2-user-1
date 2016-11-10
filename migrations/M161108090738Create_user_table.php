<?php

namespace yuncms\user\migrations;

use yii\db\Migration;

class M161108090738Create_user_table extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(11),
            'username' => $this->string()->notNull()->unique(),
            'email' => $this->string()->notNull()->unique(),
            'mobile' => $this->string(11)->unique(),
            'auth_key' => $this->string()->notNull(),
            'password_hash' => $this->string()->notNull(),
            'amount' => $this->decimal(10, 2)->defaultValue('0.00'),
            'point' => $this->integer()->defaultValue(0),
            'avatar' => $this->boolean()->defaultValue(false),
            'unconfirmed_email' => $this->string(150),
            'unconfirmed_mobile' => $this->string(11),
            'blocked_at' => $this->integer()->unsigned(),
            'followers'=> $this->integer()->defaultValue(0)->unsigned(),
            'login_num' => $this->integer()->unsigned()->defaultValue(0),
            'registration_ip' => $this->string(),
            'flags' => $this->integer()->notNull()->defaultValue(0),
            'weights' => $this->integer()->defaultValue(0),
            'login_at' => $this->integer()->unsigned(),
            'confirmed_at' => $this->integer(),
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->notNull(),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%user}}');
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
