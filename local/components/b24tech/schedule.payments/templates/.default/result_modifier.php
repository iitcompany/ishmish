<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */
use Bitrix\Main\Page\Asset;

CJSCore::Init('jquery');
Asset::getInstance()->addJs('/local/components/b24tech/schedule.payments/libs/arcticmodal/jquery.arcticmodal-0.3.min.js');
Asset::getInstance()->addCss('/local/components/b24tech/schedule.payments/libs/arcticmodal/jquery.arcticmodal-0.3.css');
Asset::getInstance()->addCss('/local/components/b24tech/schedule.payments/libs/arcticmodal/themes/simple.css');
/*
$arSumFields = array();
foreach ($arResult['FIELDS'] as $FIELD) {
    if ($FIELD['CALC']) {
        $arSumFields[$FIELD['FIELD_NAME']] = 0;
    }
}

foreach ($arResult['PERIODS'] as $key => $PERIOD) {
    foreach ($PERIOD['ITEMS'] as $k => $arItems) {
        $arResult['SUM'][$PERIOD['ID']][$k] = $arSumFields;
        foreach ($arItems as $arItem) {
            foreach ($arSumFields as $CODE => $sum) {
                //if (isset($arItem[$CODE])) {
                    $arItem[$CODE] = isset($arItem[$CODE]) ? $arItem[$CODE] : 0;
                    $arResult['SUM'][$PERIOD['ID']][$k][$CODE] = $arResult['SUM'][$PERIOD['ID']][$k][$CODE] + $arItem[$CODE];
                /*} else {
                    $arResult['SUM'][$PERIOD['ID']][$k][$CODE] = ' ';
                }
            }
            foreach ($arItem as $CODE => $vl) {
                if ($CODE != 'ID' && $CODE != 'UF_PLATFORM') {
                    if (!isset($arResult['SUM'][$PERIOD['ID']][$k][$CODE])) {
                        $arResult['SUM'][$PERIOD['ID']][$k][$CODE] = ' ';
                    }
                }
            }
        }
    }
    $arResult['TOTAL_SUM'][$PERIOD['ID']] = $arSumFields;
    foreach ($arResult['SUM'][$PERIOD['ID']] as $id => $field) {
        foreach ($arSumFields as $CODE => $vl) {
            if (isset($field[$CODE])) {
                $field[$CODE] = $field[$CODE] ? $field[$CODE] : 0;
                $arResult['TOTAL_SUM'][$PERIOD['ID']][$CODE] = $arResult['TOTAL_SUM'][$PERIOD['ID']][$CODE] + $field[$CODE];
            }
        }
    }
}*/