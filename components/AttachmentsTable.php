<?php

namespace file\components;

use file\behaviors\FileBehavior;
use file\FileModuleTrait;
use file\models\File;
use Yii;
use yii\bootstrap\Widget;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
class AttachmentsTable extends Widget
{
    use FileModuleTrait;

    /** @var FileActiveRecord */
    public $model;

    public $tableOptions = ['class' => 'table table-striped table-bordered table-condensed'];

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        if (!$this->model) {
            return Html::tag('div',
                Html::tag('b',
                    Yii::t('yii', 'Error')) . ': ' . $this->getModule()->t('attachments', 'The model cannot be empty.'
                ),
                [
                    'class' => 'alert alert-danger'
                ]
            );
        }

        $hasFileBehavior = false;
        foreach ($this->model->getBehaviors() as $behavior) {
            if (is_a($behavior, FileBehavior::className())) {
                $hasFileBehavior = true;
            }
        }
        if (!$hasFileBehavior) {
            return Html::tag('div',
                Html::tag('b',
                    Yii::t('yii', 'Error')) . ': ' . $this->getModule()->t('attachments', 'The behavior FileBehavior has not been attached to the model.'
                ),
                [
                    'class' => 'alert alert-danger'
                ]
            );
        }

        Url::remember(Url::current());
        return GridView::widget([
            'dataProvider' => new ArrayDataProvider(['allModels' => $this->model->getFiles()]),
            'layout' => '{items}',
            'tableOptions' => $this->tableOptions,
            'columns' => [
                [
                    'label' => $this->getModule()->t('attachments', 'File name'),
                    'format' => 'raw',
                    'value' => function (File $model) {
                        return Html::a("$model->name.$model->type", $model->getUrl());
                    }
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{delete}',
                    'buttons' => [
                        'delete' => function ($url, $model, $key) {
                            return Html::a('<span class="glyphicon glyphicon-trash"></span>',
                                [
                                    '/file/file/delete',
                                    'id' => $model->id
                                ],
                                [
                                    'title' => Yii::t('yii', 'Delete'),
                                    'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                    'data-method' => 'post',
                                ]
                            );
                        }
                    ]
                ],
            ]
        ]);
    }
}
