<?php

namespace file\components;

use kartik\file\FileInput;
use file\models\UploadForm;
use file\FileModuleTrait;
use yii\bootstrap\Widget;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use Yii;
use yii\web\JsExpression;
use yii\jui\JuiAsset;

/**
 * Class AttachmentsInput
 * @package File\components
 * @property FileActiveRecord $model
 */
class AttachmentsInput extends Widget
{
    use FileModuleTrait;

    public $id = 'file-input';
    /** @var FileActiveRecord $model */
    public $model;

    public $pluginOptions = [];

    public $options = [];

    public function init()
    {
        JuiAsset::register($this->view);

        parent::init();

        FileHelper::removeDirectory($this->getModule()->getUserDirPath()); // Delete all uploaded files in past

        $this->pluginOptions = array_replace(
            $this->pluginOptions,
            [
                'uploadUrl' => Url::toRoute('/file/file/upload'),
                'initialPreview' => $this->model->isNewRecord ? [] : $this->model->getInitialPreview(),
                'initialPreviewConfig' => $this->model->isNewRecord ? [] : $this->model->getInitialPreviewConfig(),
                'uploadAsync' => false,
                'otherActionButtons' =>
                    '<button type="button" class="js-caption-rename rename-button btn btn-xs btn-default" title="Переименовать" {dataKey}><i class="glyphicon glyphicon-comment text-danger"></i></button>'
                    .
                    '<input type="checkbox" class="jsFileMain" title="Главная" {dataKey}>'
                    ,
                'slugCallback' => new JsExpression(<<<JS
function(text) {return text.split(/(\\|\/)/g).pop();}
JS
                ),
                'layoutTemplates' => [
                    'footer' => '
<div class="file-thumbnail-footer">
    <div style="margin:5px 0">
        <input  class="form-control js-custom-caption" value="{caption}" />
    </div>{actions}
</div>',
                ],
            ]
        );

        $this->options = array_replace(
            $this->options,
            [
                'id' => $this->id,
                //'multiple' => true
            ]
        );
        $urlSetMain = Url::toRoute('/file/file/set-main');
        $urlRenameFile = Url::toRoute('/file/file/rename');
        $js = <<<JS
var fileInput = $('#file-input');
var form = fileInput.closest('form');
var filesUploaded = false;
var filesToUpload = 0;
var uploadButtonClicked = false;
form.on('beforeSubmit', function() { // form submit event
    if (!filesUploaded && filesToUpload) {
        console.log('upload');
        $('#file-input').fileinput('upload').fileinput('lock');

        return false;
    }
});

fileInput.on('filebatchpreupload', function() {
    uploadButtonClicked = true;
});

fileInput.on('filebatchuploadsuccess', function() {
    filesUploaded = true;
    $('#file-input').fileinput('unlock');
    if (uploadButtonClicked) {
        form.submit();
    } else {
        uploadButtonClicked = false;
    }
});

fileInput.on('filebatchselected', function(event, files) {
    filesToUpload = files.length
});

fileInput.on('filecleared', function() {
    filesToUpload = 0;
});
$('.formInput-{$this->getId()}').on('change', '.jsFileMain', function() {
    var element = $(this);
    var key = element.data('key');
    $.ajax(
        '$urlSetMain',
        {
            method: "POST",
            data: {
                id:key,
                value:element.prop('checked')
            },
            success: function(data) {
                $('.formInput-{$this->getId()} .jsFileMain').prop('checked', false);
                if(data.id) {
                     $('.formInput-{$this->getId()} .jsFileMain[data-key="' + data.id + '"]').prop('checked', true);
                }
            }
        }
    );
});

$('.formInput-{$this->getId()}').on('click', '.js-caption-rename', function() {
    var element = $(this);
    var key = element.data('key');
    var input = $(this).parents('.file-preview-frame').find('.js-custom-caption');
    var name = input.val();
    $.ajax(
        '$urlRenameFile',
        {
            method: "POST",
            data: {
                id: key,
                name: name
            },
            success: function(data) {
            }
        }
    );
});
JS;

        Yii::$app->view->registerJs($js);
    }

    public function run()
    {
        $fileInput = FileInput::widget(
            [
                'model' => new UploadForm(),
                'attribute' => 'file[]',
                'options' => $this->options,
                'pluginOptions' => $this->pluginOptions
            ]
        );

        $urlSetOrder = Url::toRoute('/file/file/set-order');
        $urlGetMainFlag = Url::toRoute('/file/file/get-main');

        Yii::$app->view->registerJs(<<<JS
$('.file-preview-thumbnails').sortable({
    update: function(event, ui) {
            var order = [];
            $('.file-preview-thumbnails .kv-file-remove:visible').each(function(k, v) {
                if($(v).data('key')) {
                    order[k] = $(v).data('key');
                }
            });

            if(order.length) {
                $.ajax(
                    '$urlSetOrder',
                    {
                        method: "POST",
                        data: {
                            order: order
                        },
                        success: function(data) {
                        }
                    }
                );
            }
        }
});

var loadMainFlag = function() {
    $.ajax(
        '$urlGetMainFlag',
        {
            method: "GET",
            data: {
                id: '{$this->model->id}',
                model: '{$this->model->formName()}'
            },
            success: function(id) {
                if(id !== 0) {
                    $('.file-preview-frame .jsFileMain[data-key='+id+']').prop('checked', true);
                }
            }
        }
    );
};

loadMainFlag();

JS
);

        return Html::tag('div', $fileInput, ['class' => 'form-group formInput-' . $this->getId()]);
    }
}