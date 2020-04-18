<?php


namespace B24Tech;

use Bitrix\Main\Loader,
    Bitrix\Im\chatTable;


class TaskHandler
{
    public function OnBeforeAddCrmEntityFromEntityChat(&$arFields)
    {
        Loader::includeModule('im');
        if (isset($arFields['DESCRIPTION']) && strpos($arFields['DESCRIPTION'], 'IM_DIALOG') !== false) {
            $tmp = explode('=', $arFields['DESCRIPTION']);
            $str = $tmp[count($tmp) - 1];
            $chatId = str_replace('chat', '', substr($str, 0, stripos($str, ']')));
            $res = chatTable::getList(array(
                'filter' => array(
                    'ID' => $chatId
                )
            ));
            if ($arChat = $res->fetch())
            {
                list($entityType, $entityId) = explode('|', $arChat['ENTITY_ID']);
                $prefix = '';
                switch ($entityType) {
                    case 'DEAL':
                        $prefix = 'D_';
                        break;
                    case 'LEAD':
                        $prefix = 'L_';
                        break;
                    case 'COMPANY':
                        $prefix = 'CO_';
                        break;
                    case 'CONTACT':
                        $prefix = 'C_';
                        break;
                }
                if (isset($entityId) && $entityId > 0) {
                    $arFields['UF_CRM_TASK'] = array(
                        $prefix.$entityId
                    );

                }
            }
        }
    }

}