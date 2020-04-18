<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */
use Bitrix\Highloadblock\HighloadBlockTable as HLBT,
    Bitrix\Main\Page\Asset,
    Bitrix\Main\EventManager;

CJSCore::Init('jquery');

require_once('agents.php');

foreach (glob(__DIR__ . "/classes/*.php") as $classFile) {
    require_once $classFile;
}

//include css
foreach (glob($_SERVER['DOCUMENT_ROOT'] . "/local/css/*.css") as $file) {
    Asset::getInstance()->addCss(str_replace($_SERVER['DOCUMENT_ROOT'], '', $file));
}

//include js
foreach (glob($_SERVER['DOCUMENT_ROOT'] . "/local/js/*.js") as $file) {
    Asset::getInstance()->addJs(str_replace($_SERVER['DOCUMENT_ROOT'], '', $file));
}

function GetEntityDataClass($HlBlockId) {
    if (empty($HlBlockId) || $HlBlockId < 1 || !CModule::IncludeModule('highloadblock'))
    {
        return false;
    }
    $hlblock = HLBT::getById($HlBlockId)->fetch();
    $entity = HLBT::compileEntity($hlblock);
    $entity_data_class = $entity->getDataClass();
    return $entity_data_class;
}

function dump($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

$eventManager = EventManager::getInstance();
$eventManager->addEventHandler(
    'tasks',
    'OnBeforeTaskAdd',
    array('B24tech\\TaskHandler', 'OnBeforeAddCrmEntityFromEntityChat')
);