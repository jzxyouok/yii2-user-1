<?php
use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use yuncms\admin\widgets\Jarvis;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel yuncms\user\backend\models\AuthenticationSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('user', 'Manage Authentication');
$this->params['breadcrumbs'][] = $this->title;
$this->registerJs("jQuery(\"#batch_deletion\").on(\"click\", function () {
    yii.confirm('" . Yii::t('app', 'Are you sure you want to delete this item?') . "',function(){
        var ids = jQuery('#gridview').yiiGridView(\"getSelectedRows\");
        jQuery.post(\"/user/authentication/batch-delete\",{ids:ids});
    });
});", View::POS_LOAD);
?>
<section id="widget-grid">
    <div class="row">
        <article class="col-xs-12 col-sm-12 col-md-12 col-lg-12 authentication-index">
            <?php Pjax::begin(); ?>
            <?php Jarvis::begin([
                'noPadding' => true,
                'editbutton' => false,
                'deletebutton' => false,
                'header' => Html::encode($this->title),
                'bodyToolbarActions' => [
                    [
                        'label' => Yii::t('user', 'Manage Authentication'),
                        'url' => ['index'],
                    ],
                    [
                        'label' => Yii::t('user', 'Create Authentication'),
                        'url' => ['create'],
                    ],
                    [
                        'options' => ['id' => 'batch_deletion', 'class' => 'btn btn-sm btn-danger'],
                        'label' => Yii::t('user', 'Batch Deletion'),
                        'url' => 'javascript:void(0);',
                    ]
                ]
            ]); ?>
            <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'options' => ['id' => 'gridview'],
                'filterModel' => $searchModel,
                'columns' => [
                    [
                        'class' => 'yii\grid\CheckboxColumn',
                        "name" => "id",
                    ],
                    //['class' => 'yii\grid\SerialColumn'],
                    'user_id',
                    'user.username',
                    'real_name',
                    'id_card',
                    [
                        'header' => Yii::t('user', 'Authentication'),
                        'attribute' => 'status',
                        'value' => function ($model) {
                            if ($model->status == 0) {
                                return Yii::t('user', 'Pending review');
                            } elseif ($model->status == 1) {
                                return Yii::t('user', 'Rejected');
                            } elseif ($model->status == 2) {
                                return Yii::t('user', 'Authenticated');
                            }
                        },
                        'format' => 'raw',
                    ],
                    'failed_reason',
                    'created_at:datetime',
                    'updated_at:datetime',
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => Yii::t('app', 'Operation'),
                        'template' => '{view} {delete}',
                        //'buttons' => [
                        //    'update' => function ($url, $model, $key) {
                        //        return $model->status === 'editable' ? Html::a('Update', $url) : '';
                        //    },
                        //],
                    ],
                ],
            ]); ?>
            <?php Jarvis::end(); ?>
            <?php Pjax::end(); ?>
        </article>
    </div>
</section>
