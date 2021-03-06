<?php

namespace yuncms\user\migrations;

use yii\db\Migration;

class M161108091359Create_doing_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE  utf8mb4_general_ci ENGINE=InnoDB';
        }
        $this->createTable('{{%user_doing}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'action' => $this->string(),
            'model_id' => $this->integer(),
            'model' => $this->string(),
            'subject' => $this->string(),
            'content' => $this->string(),
            'refer_id' => $this->integer(),
            'refer_user_id' => $this->integer(),
            'refer_content' => $this->string(),
            'created_at' => $this->integer()->unsigned()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('{{%user_doing_ibfk_1}}', '{{%user_doing}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'RESTRICT');
    }

    public function safeDown()
    {
        $this->dropTable('{{%user_doing}}');
    }
}
