<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\user\models;

use Yii;
use yii\helpers\Url;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;
use yii\base\NotSupportedException;
use yii\web\Application as WebApplication;
use yuncms\user\Module;
use yuncms\user\ModuleTrait;
use yuncms\user\helpers\Password;
use yuncms\user\UserAsset;
use yuncms\tag\models\Tag;


/**
 * User ActiveRecord model.
 *
 * @property bool $isBlocked
 * @property bool $isConfirmed
 *
 * Database fields:
 * @property integer $id
 * @property string $username
 * @property string $email
 * @property string $mobile 用户手机
 * @property string $unconfirmed_email
 * @property string $unconfirmed_mobile
 * @property string $password_hash
 * @property string $auth_key
 * @property bool $avatar
 * @property integer $registration_ip
 * @property integer $confirmed_at
 * @property integer $blocked_at
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $flags
 * @property integer $ver
 *
 * Defined relations:
 * @property Social[] $accounts
 * @property Profile $profile
 * @property Data $userData
 * @property Authentication $authentication
 *
 * Dependencies:
 * @property-read Module $module
 */
class User extends ActiveRecord implements IdentityInterface
{
    use ModuleTrait;

    const BEFORE_CREATE = 'beforeCreate';
    const AFTER_CREATE = 'afterCreate';
    const BEFORE_REGISTER = 'beforeRegister';
    const AFTER_REGISTER = 'afterRegister';
    // following constants are used on secured email changing process
    const OLD_EMAIL_CONFIRMED = 0b1;
    const NEW_EMAIL_CONFIRMED = 0b10;
    const OLD_MOBILE_CONFIRMED = 0b11;
    const NEW_MOBILE_CONFIRMED = 0b100;
    /**
     * @var string Plain password. Used for model validation.
     */
    public $password;
    /**
     * @var Profile|null
     */
    private $_profile;

    /**
     * @var Data|null
     */
    private $_userData;

    /**
     * @var string Default username regexp
     */
    public static $usernameRegexp = '/^[-a-zA-Z0-9_\x7f-\xff\.@]+$/';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior'
            ],
            'taggable' => [
                'class' => 'yuncms\tag\behaviors\TagBehavior',
                'tagValuesAsArray' => true,
                'tagRelation' => 'tags',
                'tagValueAttribute' => 'id',
                'tagFrequencyAttribute' => 'frequency',
            ],
        ];
    }

    public static function find()
    {
        return new UserQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'username' => Yii::t('user', 'Username'),
            'email' => Yii::t('user', 'Email'),
            'registration_ip' => Yii::t('user', 'Registration ip'),
            'unconfirmed_email' => Yii::t('user', 'New email'),
            'password' => Yii::t('user', 'Password'),
            'created_at' => Yii::t('user', 'Registration time'),
            'confirmed_at' => Yii::t('user', 'Confirmation time'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        return ArrayHelper::merge($scenarios, [
            'register' => ['username', 'email', 'password'],
            'connect' => ['username', 'email'],
            'create' => ['username', 'email', 'password'],
            'update' => ['username', 'email', 'password'],
            'settings' => ['username', 'email', 'password'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // username rules
            'usernameRequired' => ['username', 'required', 'on' => ['register', 'create', 'connect', 'update']],
            'usernameMatch' => ['username', 'match', 'pattern' => static::$usernameRegexp],
            'usernameLength' => ['username', 'string', 'min' => 3, 'max' => 255],
            'usernameUnique' => ['username', 'unique', 'message' => Yii::t('user', 'This username has already been taken')],
            'usernameTrim' => ['username', 'trim'],

            // email rules
            'emailRequired' => ['email', 'required', 'on' => ['register', 'connect', 'create', 'update']],
            'emailPattern' => ['email', 'email', 'checkDNS' => true],
            'emailLength' => ['email', 'string', 'max' => 255],
            'emailUnique' => ['email', 'unique', 'message' => Yii::t('user', 'This email address has already been taken')],
            'emailTrim' => ['email', 'trim'],

            // password rules
            'passwordRequired' => ['password', 'required', 'on' => ['register']],
            'passwordLength' => ['password', 'string', 'min' => 6, 'on' => ['register', 'create']],

            'tags' => ['tagValues', 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * 返回用户是否已经验证
     * @return boolean Whether the user is confirmed or not.
     */
    public function getIsConfirmed()
    {
        return $this->confirmed_at != null;
    }

    /**
     * 返回用户是否已经锁定
     * @return boolean Whether the user is blocked or not.
     */
    public function getIsBlocked()
    {
        return $this->blocked_at != null;
    }

    /**
     * 返回用户是否有头像
     * @return boolean Whether the user is blocked or not.
     */
    public function getIsAvatar()
    {
        return $this->avatar != 0;
    }

    /**
     * 获取auth_key
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * 返回用户详细资料
     * @return \yii\db\ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne(Profile::className(), ['user_id' => 'id']);
    }

    /**
     * @param Profile $profile
     */
    public function setProfile(Profile $profile)
    {
        $this->_profile = $profile;
    }

    /**
     * 返回用户附加资料
     * @return \yii\db\ActiveQuery
     */
    public function getUserData()
    {
        return $this->hasOne(Data::className(), ['user_id' => 'id']);
    }

    /**
     * 返回用户用户认证信息
     * @return \yii\db\ActiveQuery
     */
    public function getAuthentication()
    {
        return $this->hasOne(Authentication::className(), ['user_id' => 'id']);
    }

    /**
     * @param Data $data
     */
    public function setUserData(Data $data)
    {
        $this->_userData = $data;
    }

    /**
     * 获取用户关注的Tag
     */
    public function getTags()
    {
        return $this->hasMany(Tag::className(), ['id' => 'tag_id'])->viaTable('{{%user_tag}}', ['user_id' => 'id']);
    }

    /**
     * 返回所有已经连接的社交媒体账户
     * @return Social[] Connected accounts ($provider => $account)
     */
    public function getAccounts()
    {
        $connected = [];
        $accounts = $this->hasMany(Social::className(), ['user_id' => 'id'])->all();

        /**
         * @var Social $account
         */
        foreach ($accounts as $account) {
            $connected[$account->provider] = $account;
        }

        return $connected;
    }

    /**
     * @return \yii\db\ActiveQuery 返回所有已添加的教育经历
     */
    public function getEducations()
    {
        return $this->hasMany(Education::className(), ['user_id' => 'id']);
    }

    /**
     * 返回所有已添加的工作经历
     * @return \yii\db\ActiveQuery
     */
    public function getCareers()
    {
        return $this->hasMany(Career::className(), ['user_id' => 'id']);
    }

    /**
     * 获取登陆历史
     * @return \yii\db\ActiveQuery
     */
    public function getLoginHistories()
    {
        return $this->hasMany(LoginHistory::className(), ['user_id' => 'id']);
    }

    /**
     * 获取我的收藏
     * 一对多关系
     */
    public function getCollections()
    {
        return $this->hasMany(Collection::className(), ['user_id' => 'id']);
    }

    /**
     * 获取我的关注一对多关系
     */
    public function getAttentions()
    {
        return $this->hasMany(Attention::className(), ['user_id' => 'id']);
    }

    /**
     * 获取我关注
     * @return \yii\db\ActiveQueryInterface
     */
    public function getFollowers()
    {
        return $this->hasMany(Follow::className(), ['user_id' => 'id'])->andOnCondition(['model' => get_class($this)]);
    }

    /**
     * 用户我的粉丝
     * @return \yii\db\ActiveQueryInterface
     */
    public function getFans()
    {
        return $this->hasMany(Attention::className(), ['model_id' => 'id'])->andOnCondition(['model' => get_class($this)]);
    }

    /**
     * 获取我的访客
     * 一对多关系
     */
    public function getVisits()
    {
        return $this->hasMany(Visit::className(), ['source_id' => 'id']);
    }

    /**
     * 获取我的APP列表
     * 一对多关系
     * @return ActiveQuery
     */
    public function getRests()
    {
        return $this->hasMany(Rest::className(), ['user_id' => 'id']);
    }

    /**
     * 获取用户已经激活的钱包
     */
    public function getWallets()
    {
        return $this->hasMany(Wallet::className(), ['user_id' => 'id']);
    }

    /** @inheritdoc */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Creates new user account. It generates password if it is not provided by user.
     *
     * @return boolean
     */
    public function create()
    {
        if ($this->getIsNewRecord() == false) {
            throw new \RuntimeException('Calling "' . __CLASS__ . '::' . __METHOD__ . '" on existing user');
        }
        $this->confirmed_at = time();
        $this->password = $this->password == null ? Password::generate(8) : $this->password;
        $this->trigger(self::BEFORE_CREATE);
        if (!$this->save()) {
            return false;
        }
        $this->module->sendMessage($this->email, Yii::t('user', 'Welcome to {0}', Yii::$app->name), 'welcome', ['user' => $this, 'token' => null, 'module' => $this->module, 'showPassword' => true]);
        $this->trigger(self::AFTER_CREATE);
        return true;
    }

    /**
     * This method is used to register new user account. If Module::enableConfirmation is set true, this method
     * will generate new confirmation token and use mailer to send it to the user.
     *
     * @return boolean
     */
    public function register()
    {
        if ($this->getIsNewRecord() == false) {
            throw new \RuntimeException('Calling "' . __CLASS__ . '::' . __METHOD__ . '" on existing user');
        }
        $this->confirmed_at = $this->module->enableConfirmation ? null : time();
        $this->password = $this->module->enableGeneratingPassword ? Password::generate(8) : $this->password;
        $this->trigger(self::BEFORE_REGISTER);
        if (!$this->save()) {
            return false;
        }
        if ($this->module->enableConfirmation) {
            /** @var Token $token */
            $token = new Token(['type' => Token::TYPE_CONFIRMATION]);
            $token->link('user', $this);
        } else {
            Yii::$app->user->login($this, $this->module->rememberFor);
        }
        $this->module->sendMessage($this->email, Yii::t('user', 'Welcome to {0}', Yii::$app->name), 'welcome', ['user' => $this, 'token' => isset($token) ? $token : null, 'module' => $this->module, 'showPassword' => false]);
        $this->trigger(self::AFTER_REGISTER);
        return true;
    }

    /**
     * 电子邮件确认
     *
     * @param string $code Confirmation code.
     *
     * @return boolean
     */
    public function attemptConfirmation($code)
    {
        $token = Token::findOne(['user_id' => $this->id, 'code' => $code, 'type' => Token::TYPE_CONFIRMATION]);
        if ($token instanceof Token && !$token->isExpired) {
            $token->delete();
            if (($success = $this->confirm())) {
                Yii::$app->user->login($this, $this->module->rememberFor);
                $message = Yii::t('user', 'Thank you, registration is now complete.');
            } else {
                $message = Yii::t('user', 'Something went wrong and your account has not been confirmed.');
            }
        } else {
            $success = false;
            $message = Yii::t('user', 'The confirmation link is invalid or expired. Please try requesting a new one.');
        }
        Yii::$app->session->setFlash($success ? 'success' : 'danger', $message);
        return $success;
    }

    /**
     * 该方法将更新用户的电子邮件，如果`unconfirmed_email`字段为空将返回false,如果该邮件已经有人使用了将返回false; 否则返回true
     *
     * @param string $code
     *
     * @return boolean
     * @throws \Exception
     */
    public function attemptEmailChange($code)
    {
        /** @var Token $token */
        $token = Token::find()->where(['user_id' => $this->id, 'code' => $code])->andWhere(['in', 'type', [Token::TYPE_CONFIRM_NEW_EMAIL, Token::TYPE_CONFIRM_OLD_EMAIL]])->one();
        if (empty($this->unconfirmed_email) || $token === null || $token->isExpired) {
            Yii::$app->session->setFlash('danger', Yii::t('user', 'Your confirmation token is invalid or expired'));
        } else {
            $token->delete();
            if (empty($this->unconfirmed_email)) {
                Yii::$app->session->setFlash('danger', Yii::t('user', 'An error occurred processing your request'));
            } elseif (static::find()->where(['email' => $this->unconfirmed_email])->exists() == false) {
                if ($this->module->emailChangeStrategy == Module::STRATEGY_SECURE) {
                    switch ($token->type) {
                        case Token::TYPE_CONFIRM_NEW_EMAIL:
                            $this->flags |= self::NEW_EMAIL_CONFIRMED;
                            Yii::$app->session->setFlash('success', Yii::t('user', 'Awesome, almost there. Now you need to click the confirmation link sent to your old email address'));
                            break;
                        case Token::TYPE_CONFIRM_OLD_EMAIL:
                            $this->flags |= self::OLD_EMAIL_CONFIRMED;
                            Yii::$app->session->setFlash('success', Yii::t('user', 'Awesome, almost there. Now you need to click the confirmation link sent to your new email address'));
                            break;
                    }
                }
                if ($this->module->emailChangeStrategy == Module::STRATEGY_DEFAULT || ($this->flags & self::NEW_EMAIL_CONFIRMED && $this->flags & self::OLD_EMAIL_CONFIRMED)) {
                    $this->email = $this->unconfirmed_email;
                    $this->unconfirmed_email = null;
                    Yii::$app->session->setFlash('success', Yii::t('user', 'Your email address has been changed'));
                }
                $this->save(false);
            }
        }
    }

    /**
     * 该方法将更新用户的手机，如果`unconfirmed_mobile`字段为空将返回false,如果该邮件已经有人使用了将返回false; 否则返回true
     *
     * @param string $code
     *
     * @return boolean
     * @throws \Exception
     */
    public function attemptMobileChange($code)
    {
        /** @var Token $token */
        $token = Token::find()->where(['user_id' => $this->id, 'code' => $code])->andWhere(['in', 'type', [Token::TYPE_CONFIRM_NEW_MOBILE, Token::TYPE_CONFIRM_OLD_MOBILE]])->one();
        if (empty($this->unconfirmed_mobile) || $token === null || $token->isExpired) {
            Yii::$app->session->setFlash('danger', Yii::t('user', 'Your confirmation token is invalid or expired'));
        } else {
            $token->delete();
            if (empty($this->unconfirmed_mobile)) {
                Yii::$app->session->setFlash('danger', Yii::t('user', 'An error occurred processing your request'));
            } elseif (static::find()->where(['mobile' => $this->unconfirmed_mobile])->exists() == false) {
                if ($this->module->mobileChangeStrategy == Module::STRATEGY_SECURE) {
                    switch ($token->type) {
                        case Token::TYPE_CONFIRM_NEW_MOBILE:
                            $this->flags |= self::NEW_MOBILE_CONFIRMED;
                            Yii::$app->session->setFlash('success', Yii::t('user', 'Awesome, almost there. Now you need to input the confirmation code sent to your old mobile'));
                            break;
                        case Token::TYPE_CONFIRM_OLD_MOBILE:
                            $this->flags |= self::OLD_MOBILE_CONFIRMED;
                            Yii::$app->session->setFlash('success', Yii::t('user', 'Awesome, almost there. Now you need to input the confirmation code sent to your new mobile'));
                            break;
                    }
                }
                if ($this->module->mobileChangeStrategy == Module::STRATEGY_DEFAULT || ($this->flags & self::NEW_MOBILE_CONFIRMED && $this->flags & self::OLD_MOBILE_CONFIRMED)) {
                    $this->mobile = $this->unconfirmed_mobile;
                    $this->unconfirmed_mobile = null;
                    Yii::$app->session->setFlash('success', Yii::t('user', 'Your mobile address has been changed'));
                }
                $this->save(false);
            }
        }
    }

    /**
     * 设置用户已经验证
     */
    public function confirm()
    {
        return (bool)$this->updateAttributes(['confirmed_at' => time()]);
    }

    /**
     * 设置最后登录时间
     * @return void
     */
    public function resetLoginData()
    {
        $this->userData->updateAttributes(['login_at' => time()]);
        $this->userData->updateAttributes(['login_ip' => Yii::$app->request->userIP]);
        $this->userData->updateCounters(['login_num' => 1]);
    }

    /**
     * 重置密码
     *
     * @param string $password
     *
     * @return boolean
     */
    public function resetPassword($password)
    {
        return (bool)$this->updateAttributes(['password_hash' => Password::hash($password)]);
    }

    /**
     * 锁定用户
     */
    public function block()
    {
        return (bool)$this->updateAttributes(['blocked_at' => time(), 'auth_key' => Yii::$app->security->generateRandomString()]);
    }

    /**
     * 解除用户锁定
     */
    public function unblock()
    {
        return (bool)$this->updateAttributes(['blocked_at' => null]);
    }

    /**
     * 使用email地址生成一个新的用户名
     */
    public function generateUsername()
    {
        // try to use name part of email
        $this->username = explode('@', $this->email)[0];
        if ($this->validate(['username'])) {
            return $this->username;
        }

        // generate username like "user1", "user2", etc...
        while (!$this->validate(['username'])) {
            $row = (new Query())->from('{{%user}}')->select('MAX(id) as id')->one();
            $this->username = 'user' . ++$row['id'];
        }

        return $this->username;
    }

    /**
     * 创建 "记住我" 身份验证Key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * 获取头像Url
     * @param string $size
     * @return string
     */
    public function getAvatar($size = 'big')
    {
        $size = in_array($size, ['big', 'middle', 'small']) ? $size : 'big';
        if ($this->getIsAvatar()) {
            $avatarFileName = "_avatar_$size.jpg";
            return $this->getModule()->getAvatarUrl($this->id) . $avatarFileName;
        } else {
            switch ($size) {
                case 'big':
                    $avatarUrl = '/img/no_avatar_big.gif';
                    break;
                case 'middle':
                    $avatarUrl = '/img/no_avatar_middle.gif';
                    break;
                case 'small':
                    $avatarUrl = '/img/no_avatar_small.gif';
                    break;
                default:
                    $avatarUrl = '/img/no_avatar_big.gif';
            }
            if (!file_exists(Yii::getAlias('@webroot/img/no_avatar_big.gif'))) {
                $baseUrl = UserAsset::register(Yii::$app->view)->baseUrl;
            } else {
                $baseUrl = '';
            }
            return Url::to($baseUrl . $avatarUrl, true);
        }
    }

    /**
     * 是否已经收藏过Source和ID
     * @param string $sourceType
     * @param int $sourceId
     * @return bool
     */
    public function isCollected($sourceType, $sourceId)
    {
        return $this->getCollections()->andWhere(['model' => $sourceType, 'model_id' => $sourceId])->exists();
    }

    /**
     * 是否已关注指定的Source和ID
     * @param string $sourceType
     * @param int $sourceId
     * @return mixed
     */
    public function isFollowed($sourceType, $sourceId)
    {
        return $this->getAttentions()->andWhere(['model' => $sourceType, 'model_id' => $sourceId])->exists();
    }

    /**
     * 是否实名认证
     * @return bool
     */
    public function isAuthentication()
    {
        if ($this->authentication && $this->authentication->status == Authentication::STATUS_AUTHENTICATED) {
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->generateAuthKey();
            if (Yii::$app instanceof WebApplication) {
                $this->registration_ip = Yii::$app->request->getUserIP();
            }
        }
        if (!empty($this->password)) {
            $this->password_hash = Password::hash($this->password);
        }
        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            if ($this->_profile == null) {
                $this->_profile = new Profile();
            }
            $this->_profile->link('user', $this);

            if ($this->_userData == null) {
                $this->_userData = new Data();
            }
            $this->_userData->link('user', $this);
        }
    }

    /**
     * 定义乐观锁
     * @return string
     */
//    public function optimisticLock()
//    {
//        return 'ver';
//    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('Method "' . __CLASS__ . '::' . __METHOD__ . '" is not implemented.');
    }

    /**
     * 通过用户名或者用户登陆邮箱获取用户
     * @param $usernameOrEmail
     * @return User|null
     */
    public static function findByUsernameOrEmail($usernameOrEmail)
    {
        if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            return static::findByEmail($usernameOrEmail);
        }
        return static::findByUsername($usernameOrEmail);
    }

    /**
     * 通过邮箱获取用户
     * @param string $email 邮箱
     * @return null|static
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email]);
    }

    /**
     * 通过手机号获取用户
     * @param string $mobile
     * @return static
     */
    public static function findByMobile($mobile)
    {
        return static::findOne(['mobile' => $mobile]);
    }

    /**
     * 通过用户名获取用户
     * @param string $username 用户名
     * @return null|static
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }
}
