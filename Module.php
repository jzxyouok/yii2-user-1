<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\user;

use Yii;
use yii\helpers\FileHelper;
use yuncms\user\models\User;
use yuncms\user\models\Doing;
use yuncms\user\models\PurseLog;
use yuncms\user\models\Notification;

/**
 * This is the main module class for the yii2-user.
 */
class Module extends \yii\base\Module
{
    /**
     * Email is changed right after user enter's new email address.
     */
    const STRATEGY_INSECURE = 0;

    /**
     * Email is changed after user clicks confirmation link sent to his new email address.
     */
    const STRATEGY_DEFAULT = 1;

    /**
     * Email is changed after user clicks both confirmation links sent to his old and new email addresses.
     */
    const STRATEGY_SECURE = 2;

    /**
     * @var bool Whether to show flash messages.
     */
    public $enableFlashMessages = false;

    /**
     * @var bool Whether to enable registration.
     */
    public $enableRegistration = true;

    /**
     * @var bool Whether to enable registration captcha.
     */
    public $enableRegistrationCaptcha = false;

    /**
     * @var bool Whether to remove password field from registration form.
     */
    public $enableGeneratingPassword = false;

    /**
     * @var bool Whether user has to confirm his account.
     */
    public $enableConfirmation = false;

    /**
     * @var bool Whether to allow logging in without confirmation.
     */
    public $enableUnconfirmedLogin = false;

    /**
     *
     * @var bool Whether to enable password recovery.
     */
    public $enablePasswordRecovery = true;

    /**
     * @var int Email changing strategy.
     */
    public $emailChangeStrategy = self::STRATEGY_DEFAULT;

    /**
     * @var int The time you want the user will be remembered without asking for credentials.
     */
    public $rememberFor = 1209600; // two weeks

    /**
     * @var int The time before a confirmation token becomes invalid.
     */
    public $confirmWithin = 86400; // 24 hours

    /**
     * @var int The time before a recovery token becomes invalid.
     */
    public $recoverWithin = 21600; // 6 hours

    /**
     * @var int Cost parameter used by the Blowfish hash algorithm.
     */
    public $cost = 10;

    /**
     * @var array Mailer configuration
     */
    public $mailViewPath = '@vendor/yuncms/yii2-user/views/mail';

    /**
     * @var string|array Default: `Yii::$app->params['adminEmail']` OR `no-reply@example.com`
     */
    public $mailSender;

    /**
     * @var string the default route of this module. Defaults to 'default'.
     * The route may consist of child module ID, controller ID, and/or action ID.
     * For example, `help`, `post/create`, `admin/post/create`.
     * If action ID is not given, it will take the default value as specified in
     * [[Controller::defaultAction]].
     */
    public $defaultRoute = 'profile';

    /**
     * @var string The prefix for user module URL.
     *
     * @See [[GroupUrlRule::prefix]]
     */
    public $urlPrefix = 'user';

    /** @var array The rules to be used in URL management. */
    public $urlRules = [
        '<id:\d+>' => 'profile/show',
        '<action:(login|logout)>' => 'security/<action>',
        '<action:(register|resend)>' => 'registration/<action>',
        'confirm/<id:\d+>/<code:[A-Za-z0-9_-]+>' => 'registration/confirm',
        'forgot' => 'recovery/request',
        'notice' => 'notification/index',
        'recover/<id:\d+>/<code:[A-Za-z0-9_-]+>' => 'recovery/reset',
        'setting/<action:\w+>' => 'setting/<action>'
    ];

    public $avatarUrl = '@uploadUrl/avatar';

    public $avatarPath = '@uploads/avatar';

    /**
     * 获取头像的存储路径
     * @param int $userId
     * @return string
     */
    public function getAvatarPath($userId)
    {
        $avatarPath = Yii::getAlias($this->avatarPath) . '/' . $this->getAvatarHome($userId);
        if (!is_dir($avatarPath)) {
            FileHelper::createDirectory($avatarPath);
        }
        return $avatarPath . substr($userId, -2);
    }

    /**
     * 获取头像访问Url
     * @param int $userId 用户ID
     * @return string
     */
    public function getAvatarUrl($userId)
    {
        return Yii::getAlias($this->avatarUrl) . '/' . $this->getAvatarHome($userId) . substr($userId, -2);
    }

    /**
     * 获取头像路径
     *
     * @param int $userId 用户ID
     * @return string
     */
    public function getAvatarHome($userId)
    {
        $id = sprintf("%09d", $userId);
        $dir1 = substr($id, 0, 3);
        $dir2 = substr($id, 3, 2);
        $dir3 = substr($id, 5, 2);
        return $dir1 . '/' . $dir2 . '/' . $dir3 . '/';
    }

    /**
     * 给用户发送邮件
     * @param string $to 收件箱
     * @param string $subject 标题
     * @param string $view 视图
     * @param array $params 参数
     * @return boolean
     */
    public function sendMessage($to, $subject, $view, $params = [])
    {
        /** @var \yii\mail\BaseMailer $mailer */
        $mailer = Yii::$app->mailer;
        $mailer->viewPath = $this->mailViewPath;
        $mailer->getView()->theme = Yii::$app->view->theme;
        $message = $mailer->compose(['html' => $view, 'text' => 'text/' . $view], $params)->setTo($to)->setSubject($subject);
        if ($this->mailSender != null) {
            $message->setFrom($this->mailSender);
        }
//        else if (isset(Yii::$app->params['adminEmail'])) {
//            $message->setFrom(Yii::$app->params['adminEmail']);
//        }
        return $message->send();
    }

    /**
     * amount变动
     * @param $userId
     * @param $value
     * @param $action
     * @param $sourceType
     * @param $sourceId
     * @param $subject
     * @return bool
     * @throws \yii\db\Exception
     */
    public function amount($userId, $value, $action, $sourceType, $sourceId = 0, $subject = '')
    {
        $transaction = User::getDb()->beginTransaction();
        try {
            $user = User::findOne($userId);
            if ($user) {
                $user->amount = $user->amount + $value;
                $user->save();
                $log = new PurseLog ([
                    'user_id' => $userId,
                    'currency' => 'amount',
                    'value' => $value,
                    'action' => $action,
                    'source_id' => $sourceId,
                    'source_type' => $sourceType,
                    'subject' => $subject,
                ]);
                if ($value > 0) {
                    $log->type = PurseLog::TYPE_INC;
                } else {
                    $log->type = PurseLog::TYPE_DEC;
                }
                $log->save();
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
        return true;
    }

    /**
     * 积分变动
     * @param $userId
     * @param $value
     * @param $action
     * @param $sourceType
     * @param $sourceId
     * @param $subject
     * @return bool
     * @throws \yii\db\Exception
     */
    public function point($userId, $value, $action, $sourceType, $sourceId = 0, $subject = '')
    {
        $transaction = User::getDb()->beginTransaction();
        try {
            $user = User::findOne($userId);
            if ($user) {
                $user->point = $user->point + $value;
                $user->save();
                $log = new PurseLog ([
                    'user_id' => $userId,
                    'currency' => 'point',
                    'value' => $value,
                    'action' => $action,
                    'source_id' => $sourceId,
                    'source_type' => $sourceType,
                    'subject' => $subject,
                ]);
                if ($value > 0) {
                    $log->type = PurseLog::TYPE_INC;
                } else {
                    $log->type = PurseLog::TYPE_DEC;
                }
                $log->save();
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
        return true;
    }

    /**
     * 发送用户通知
     * @param int $fromUserId
     * @param int $toUserId
     * @param string $type
     * @param string $subject
     * @param int $sourceId
     * @param string $referContent
     * @return static
     */
    public function notify($fromUserId, $toUserId, $type, $subject = '', $sourceId = 0, $referContent = '')
    {
        /*不能自己给自己发通知*/
        if ($fromUserId == $toUserId) {
            return false;
        }
        try {
            $notify = new Notification([
                'user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'type' => $type,
                'subject' => $subject,
                'source_id' => $sourceId,
                'refer_content' => strip_tags($referContent),
                'status' => Notification::STATUS_UNREAD
            ]);
            return $notify->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 记录用户动态
     * @param int $userId 动态发起人
     * @param string $action 动作 ['ask','answer',...]
     * @param string $sourceType 被引用的内容类型
     * @param int $sourceId 问题或文章ID
     * @param string $subject 问题或文章标题
     * @param string $content 回答或评论内容
     * @param int $referId 问题或者文章ID
     * @param int $referUserId 引用内容作者ID
     * @param null $referContent 引用内容
     * @return bool
     */
    public function doing($userId, $action, $sourceType, $sourceId, $subject, $content = '', $referId = 0, $referUserId = 0, $referContent = null)
    {
        try {
            $doing = new Doing([
                'user_id' => $userId,
                'action' => $action,
                'source_id' => $sourceId,
                'source_type' => $sourceType,
                'subject' => $subject,
                'content' => strip_tags($content),
                'refer_id' => $referId,
                'refer_user_id' => $referUserId,
                'refer_content' => strip_tags($referContent),
            ]);
            return $doing->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $category
     * @param $message
     * @param array $params
     * @param null $language
     * @return string
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t('user/' . $category, $message, $params, $language);
    }


}
