<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\user\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;


/**
 * This is the model class for table "education".
 *
 * @property integer $user_id
 * @property string $school
 * @property string $department
 * @property string $date
 * @property string $degree
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property User $user
 */
class Career extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_career}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [TimestampBehavior::className()];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['name', 'filter', 'filter' => 'trim'],
            ['name', 'required'],

            ['position', 'filter', 'filter' => 'trim'],
            ['position', 'required'],

            ['city', 'filter', 'filter' => 'trim'],
            ['city', 'required'],

            ['description', 'filter', 'filter' => 'trim'],
            ['description', 'required'],

            ['start_at', 'filter', 'filter' => 'trim'],
            ['start_at', 'required'],

            ['end_at', 'filter', 'filter' => 'trim'],
            ['end_at', 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Yii::t('user', 'Career name'),
            'position' => Yii::t('user', 'Career Position'),
            'city' => Yii::t('user', 'Career City'),
            'start_at' => Yii::t('user', 'Career Start At'),
            'end_at' => Yii::t('user', 'Career End At'),
            'description' => Yii::t('user', 'Career Description'),
        ];
    }

    /**
     * @return \yii\db\ActiveQueryInterface
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            $this->link('user', Yii::$app->user->identity);
        }
    }
}