<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */
namespace yuncms\user\models;

use Yii;
use yii\db\ActiveRecord;
use yuncms\user\ModuleTrait;

/**
 * This is the model class for table "profile".
 *
 * @property integer $user_id 用户ID
 * @property string $name
 * @property string $public_email
 * @property string $location
 * @property string $website
 * @property string $bio
 * @property User $user
 *
 * @author Xu Tongle <xutongle@gmail.com>
 */
class Profile extends ActiveRecord
{
    use ModuleTrait;

    // 未选择
    const GENDER_UNCONFIRMED = 0;
    // 男
    const GENDER_MALE = 1;
    // 女
    const GENDER_FEMALE = 2;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_profile}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['mobile', 'string', 'min' => 11, 'max' => 11],
            ['gender', 'default', 'value' => self::GENDER_UNCONFIRMED],
            ['gender', 'in', 'range' => [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_UNCONFIRMED]],
            ['public_email', 'email'],
            ['website', 'url'],
            ['address', 'string'],
            ['introduction', 'string'],
            [['public_email', 'name', 'timezone', 'location', 'website'], 'string', 'max' => 255],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Yii::t('user', 'Name'),
            'gender' => Yii::t('user', 'Gender'),
            'mobile' => Yii::t('user', 'Mobile'),
            'public_email' => Yii::t('user', 'Email (public)'),
            'location' => Yii::t('user', 'Location'),
            'address' => Yii::t('user', 'Address'),
            'website' => Yii::t('user', 'Website'),
            'timezone' => Yii::t('user', 'Time zone'),
            'bio' => Yii::t('user', 'Bio'),
            'introduction' => Yii::t('user', 'Introduction'),
        ];
    }

    /**
     * 获取性别的字符串标识
     */
    public function getGenderName()
    {
        switch ($this->gender) {
            case self::GENDER_UNCONFIRMED:
                $genderName = Yii::t('user', 'Unconfirmed');
                break;
            case self::GENDER_MALE:
                $genderName = Yii::t('user', 'Male');
                break;
            case self::GENDER_FEMALE:
                $genderName = Yii::t('user', 'Female');
                break;
            default:
                throw new \RuntimeException('Your database is not supported!');
        }
        return $genderName;
    }

    /**
     * @return \yii\db\ActiveQueryInterface
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}