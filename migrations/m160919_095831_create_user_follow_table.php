<?php

use yii\db\Migration;

/**
 * Handles the creation for table `user_follow`.
 */
class m160919_095831_create_user_follow_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE  utf8mb4_general_ci ENGINE=InnoDB';
        }

        /**
         * 关注表
         */
        $this->createTable('{{%user_follow}}', [
            'id' => $this->primaryKey(11),
            'user_id' => $this->integer()->notNull(),
            'follow_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->notNull(),
        ], $tableOptions);
        $this->addForeignKey('{{%user_follow_ibfk_1}}', '{{%user_follow}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'RESTRICT');
        $this->addForeignKey('{{%user_follow_ibfk_2}}', '{{%user_follow}}', 'follow_id', '{{%user}}', 'id', 'CASCADE', 'RESTRICT');
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('{{%user_follow}}');
    }
}
