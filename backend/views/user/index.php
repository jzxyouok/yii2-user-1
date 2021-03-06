<?php
use yii\web\View;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use yuncms\admin\widgets\Jarvis;
use yuncms\user\backend\models\UserSearch;
use yuncms\user\models\Authentication;

/**
 * @var View $this
 * @var ActiveDataProvider $dataProvider
 * @var UserSearch $searchModel
 */

$this->title = Yii::t('user', 'Manage Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<section id="widget-grid">
    <div class="row">
        <article class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <?php Jarvis::begin([
                'noPadding' => true,
                'editbutton' => false,
                'deletebutton' => false,
                'header' => Html::encode($this->title),
                'bodyToolbarActions' => [
                    [
                        'label' => Yii::t('user', 'Manage User'),
                        'url' => ['/user/user/index'],
                    ],
                    [
                        'label' => Yii::t('user', 'Create User'),
                        'url' => ['/user/user/create'],
                    ],
                ]
            ]); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    'id',
                    'username',
                    'email:email',
                    //'userData.amount',
                    //'userData.point',
                    [
                        'header' => Yii::t('user', 'Authentication'),
                        'value' => function ($model) {
                            if ($model->authentication) {
                                if ($model->authentication->status == Authentication::STATUS_PENDING) {
                                    return Yii::t('user', 'Pending review');
                                } elseif ($model->authentication->status == Authentication::STATUS_REJECTED) {
                                    return Yii::t('user', 'Rejected');
                                } elseif ($model->authentication->status == Authentication::STATUS_AUTHENTICATED) {
                                    return Yii::t('user', 'Authenticated');
                                }
                            }
                            return Yii::t('user', 'UnSubmitted');
                        },
                        'format' => 'raw',
                    ],
                    [
                        'attribute' => 'registration_ip',
                        'value' => function ($model) {
                            return $model->registration_ip == null
                                ? '<span class="not-set">' . Yii::t('app', '(not set)') . '</span>'
                                : $model->registration_ip;
                        },
                        'format' => 'html',
                    ],
                    [
                        'attribute' => 'created_at',
                        'format' => 'datetime',
                        'filter' => \yii\jui\DatePicker::widget([
                            'model' => $searchModel,
                            'options' => [
                                'class' => 'form-control'
                            ],
                            'attribute' => 'created_at',
                            'name' => 'created_at',
                            'dateFormat' => 'yyyy-MM-dd'
                        ]),
                    ],
                    [
                        'header' => Yii::t('user', 'Confirmation'),
                        'value' => function ($model) {
                            if ($model->isConfirmed) {
                                return '<div class="text-center"><span class="text-success">' . Yii::t('user', 'Confirmed') . '</span></div>';
                            } else {
                                return Html::a(Yii::t('user', 'Confirm'), ['confirm', 'id' => $model->id], [
                                    'class' => 'btn btn-xs btn-success btn-block',
                                    'data-method' => 'post',
                                    'data-confirm' => Yii::t('user', 'Are you sure you want to confirm this user?'),
                                ]);
                            }
                        },
                        'format' => 'raw',
                        'visible' => Yii::$app->getModule('user')->enableConfirmation,
                    ],
                    [
                        'header' => Yii::t('user', 'Block status'),
                        'value' => function ($model) {
                            if ($model->isBlocked) {
                                return Html::a(Yii::t('user', 'Unblock'), ['block', 'id' => $model->id], [
                                    'class' => 'btn btn-xs btn-success btn-block',
                                    'data-method' => 'post',
                                    'data-confirm' => Yii::t('user', 'Are you sure you want to unblock this user?'),
                                ]);
                            } else {
                                return Html::a(Yii::t('user', 'Block'), ['block', 'id' => $model->id], [
                                    'class' => 'btn btn-xs btn-danger btn-block',
                                    'data-method' => 'post',
                                    'data-confirm' => Yii::t('user', 'Are you sure you want to block this user?'),
                                ]);
                            }
                        },
                        'format' => 'raw',
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{assignment} {update} {delete}',
                        'buttons' => [
                            'assignment' => function ($url, $model, $key) {
                                $title = Yii::t('user', 'Assignment');
                                $icon = Html::tag('span', '', ['class' => "glyphicon glyphicon-eye-open"]);
                                return Html::a($icon, ['/admin/assignment/view', 'id' => $model->id], [
                                    'title' => $title,
                                    'aria-label' => $title,
                                    'data-pjax' => '0',
                                ]);;
                            },
                        ],
                    ],
                ]
            ]); ?>
            <?php Jarvis::end(); ?>
        </article>
    </div>
</section>
