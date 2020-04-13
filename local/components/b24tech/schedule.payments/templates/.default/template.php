<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

?>
<div class="crm-lead-schedule-payment-wrapper" data-id="<?= $arResult['ID'] ?>">
    <div class="crm-lead-schedule-payment_head">
        <div class="crm-lead-schedule-payment_head-default">
            <select>
                <?foreach ($arResult['PERIODS'] as $arPeriod) {
                    $name = $arPeriod['UF_NAME'] ?: date_format(date_create($arPeriod['UF_DATE_START']), 'd.m.Y').' - '.date_format(date_create($arPeriod['UF_DATE_END']), 'd.m.Y')
                    ?>
                    <option value="<?=$arPeriod['ID']?>" <?=$arPeriod['UF_DEFAULT'] ? 'selected' : ''?>><?=$name?></option>
                <?}?>
            </select>
            <button class="js-init-btn_default ui-btn ui-btn-xs ui-btn-primary" data-action="show_modal">Установить шаблон</button>
        </div>
        <div class="crm-lead-schedule-payment_head-default">
            <select>
                <?foreach ($arResult['PERIODS'] as $arPeriod) {
                    $name = $arPeriod['UF_NAME'] ?: date_format(date_create($arPeriod['UF_DATE_START']), 'd.m.Y').' - '.date_format(date_create($arPeriod['UF_DATE_END']), 'd.m.Y')
                    ?>
                    <option value="<?=$arPeriod['ID']?>" <?=$arPeriod['UF_DEFAULT'] ? 'selected' : ''?>><?=$name?></option>
                <?}?>
            </select>
            <button class="js-init-btn_default-copy ui-btn ui-btn-xs ui-btn-secondary" data-action="show_modal">Копировать период</button>
        </div>
        <button class="js-init-btn ui-btn ui-btn-success" data-action="show_modal">Добавить период</button>
    </div>

    <?
    $period_index = 1;
    $isExpand = false;
    foreach ($arResult['PERIODS'] as $arPeriod) {
        if ($_REQUEST['action'] == 'update') {
            if ($_REQUEST['period_id'] == $arPeriod['ID']) {
                $type = 'expand';
            } else {
                $type = 'collapse';
            }
        } else {
            if ($period_index == 1) {
                $type = 'expand';
            } else {
                $type = 'collapse';
            }
        }
        ?>
        <div class="schedule-payments__period table-<?=$type?> <?=$arPeriod['UF_STATUS'] == 'Y' ? 'period_create' : ''?> <?=$arPeriod['UF_DEFAULT'] ? 'period_default' : ''?>">
            <?=$arPeriod['UF_DEFAULT'] ? '<div class="period_default-name">ШАБЛОН</div>' : ''?>
            <div class="js-init-collapse btn-period btn-<?=$type?>"><?=$arPeriod['UF_NAME']?> (<span><?=$type == 'expand' ? 'Свернуть' : 'Развернуть'?></span>)</div>
            <div class="schedule-payments__period-head" data-period-id="<?= $arPeriod['ID'] ?>">
                <div class="period-head__title">
                    <div class="period-head__fields">
                        <?
                        $last_period = $i_period == count();
                        $i = 1;
                        foreach ($arResult['PERIOD_FIELDS'] as $FIELD) {
                            if ($FIELD['FIELD_NAME'] != 'UF_NAME' && $FIELD['FIELD_NAME'] != 'UF_AUTO_RENEWAL' && $FIELD['FIELD_NAME'] != 'UF_DEFAULT' || ($FIELD['FIELD_NAME'] == 'UF_AUTO_RENEWAL' && $arPeriod['UF_DEFAULT'] == 1)) {
                                ?>
                            <div class="period-field__group">
                                <div class="period-field__group-label">
                                    <?= $arResult['PERIOD_FIELDS'][$FIELD['FIELD_NAME']]['LIST_COLUMN_LABEL'] ?>:
                                </div>
                                <? switch ($FIELD['USER_TYPE_ID']) {
                                    case 'date':
                                        ?>
                                        <div data-name="<?= $FIELD['FIELD_NAME'] ?>"
                                             data-type="<?= $FIELD['USER_TYPE_ID'] ?>"
                                             data-value="<?= strlen($arPeriod[$FIELD['FIELD_NAME']]) > 0 ? date_format(date_create($arPeriod[$FIELD['FIELD_NAME']]), 'Y-m-d') : '' ?>"
                                             class="period-field__group-value type-<?= $FIELD['USER_TYPE_ID'] ?>">
                                            <?= $arPeriod[$FIELD['FIELD_NAME']] ?: 'Не указано' ?>
                                        </div>
                                        <?
                                        break;
                                    case 'enumeration':
                                        ?>
                                            <div data-name="<?= $FIELD['FIELD_NAME'] ?>"
                                                 data-type="<?= $FIELD['USER_TYPE_ID'] ?>"
                                                 data-values='<?= json_encode($arResult['PERIOD_FIELDS'][$FIELD['FIELD_NAME']]['VALUES']) ?>'
                                                 data-value="<?= $arPeriod[$FIELD['FIELD_NAME']] ?>"
                                                 class="period-field__group-value type-<?= $FIELD['USER_TYPE_ID'] ?>">
                                                <?= $arResult['PERIOD_FIELDS'][$FIELD['FIELD_NAME']]['VALUES'][$arPeriod[$FIELD['FIELD_NAME']]]['VALUE'] ?>
                                            </div>
                                        <?

                                        break;
                                    case 'string':
                                        ?>
                                        <div data-name="<?= $FIELD['FIELD_NAME'] ?>"
                                             data-type="<?= $FIELD['USER_TYPE_ID'] ?>"
                                             data-value="<?= $arPeriod[$FIELD['FIELD_NAME']]?>"
                                             class="period-field__group-value type-<?= $FIELD['USER_TYPE_ID'] ?>">
                                            <?if (strpos($FIELD['FIELD_NAME'], 'UF_BALANCE') !== false) {?>
                                                <?=$arPeriod[$FIELD['FIELD_NAME']] ?: 0?>
                                            <?} else {?>
                                                <?=$arPeriod[$FIELD['FIELD_NAME']] ?: 'Не указано'?>
                                            <?}?>
                                        </div>
                                        <?
                                        break;
                                } ?>
                            </div>
                        <? } ?>
                        <? } ?>
                    </div>

                    <div class="period-head__title-edit">
                        <span class="btn-icon btn-edit js-init-change-period" title="Редактировать">
                            <span class="btn-icon-edit"></span>
                        </span>
                        <?if ($arPeriod['UF_DEFAULT'] != '1') {?>
                            <span class="btn-icon btn-edit js-init-delete-period" title="Удалить">
                                <span class="btn-icon-delete"></span>
                            </span>
                        <?}?>
                        <span class="btn-icon btn-save js-init-save-period" title="Сохранить" style="display: none">
                            <span class="btn-icon-apply"></span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="schedule-payments__period-body">
                <? if ($arPeriod['ITEMS']) { ?>
                    <table class="schedule-payments__table">
                        <thead>
                        <tr>
                            <? foreach ($arResult['FIELDS'] as $arField) { ?>
                                <th><span><?= $arField['LIST_COLUMN_LABEL'] ?></span></th>
                            <? } ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?

                        foreach ($arPeriod['ITEMS'] as $platform_id => $arItems) {
                            $i = 1;

                            foreach ($arItems as $arItem) {
                                $rowspan = count($arItems) > 1 && $i == 1 ? count($arItems) : 1;
                                $last_row = $i == count($arItems);
                                $display = count($arItems) > 1 && $i > 1 ? false : true;
                                ?>
                                <tr data-i="<?= $i ?>"
                                    data-row-id="<?= $arItem['ID'] ?>">
                                    <?
                                    $index = 1;
                                    foreach ($arResult['FIELDS'] as $arField) {
                                        if (strpos($arField['FIELD_NAME'], 'UF_') !== false) {
                                            $edit = true;
                                            switch ($arField['FIELD_NAME']) {
                                                case 'UF_SPEND_NDS':
                                                case 'UF_BUDGET_PERIOD':
                                                case 'UF_BALANCE':
                                                case 'UF_BALANCE_FACT':
                                                case 'UF_PLATFORM':
                                                case 'UF_TARGET':
                                                    $edit = false;
                                                    break;
                                            }

                                            switch ($arField['USER_TYPE_ID']) {
                                                case 'string':
                                                    ?>
                                                    <td>
                                                        <div data-type="<?= $arField['USER_TYPE_ID'] ?>"
                                                             data-name="<?= $arField['FIELD_NAME'] ?>"
                                                             data-value="<?= $arItem[$arField['FIELD_NAME']] ?>"
                                                             class="<?=$edit ? 'js-init-field-change' : ''?>">
                                                            <?= $arItem[$arField['FIELD_NAME']] ?>
                                                        </div>
                                                    </td>
                                                    <?

                                                    $index++;
                                                    break;
                                                case 'enumeration':
                                                    ?>
                                                    <?
                                                    if ($display == false && $index == 2) {

                                                    } else { ?>
                                                        <td <?= $rowspan > 1 && $index == 2 ? 'rowspan="' . $rowspan  . '" class="last_row_td"' : '' ?>>
                                                            <div data-type="<?= $arField['USER_TYPE_ID'] ?>"
                                                                 data-name="<?= $arField['FIELD_NAME'] ?>"
                                                                 data-value="<?= $arItem[$arField['FIELD_NAME']] ?>"
                                                                 data-values='<?= json_encode($arField['VALUES']) ?>'
                                                                 class="<?=$edit ? 'js-init-field-change' : ''?>">
                                                                <?= $arField['VALUES'][$arItem[$arField['FIELD_NAME']]]['VALUE'] ?>
                                                            </div>
                                                        </td>
                                                        <?
                                                    }
                                                    $index++;
                                                    break;
                                                case 'boolean':
                                                    ?>
                                                    <td>
                                                        <div data-type="<?= $arField['USER_TYPE_ID'] ?>"
                                                             data-name="<?= $arField['FIELD_NAME'] ?>"
                                                             data-value="<?= $arItem[$arField['FIELD_NAME']] ?>"
                                                             class="<?=$edit ? 'js-init-field-change' : ''?>">
                                                            <input type="checkbox"
                                                                   name="<?= $arField['FIELD_NAME'] ?>"
                                                                   value="<?= $arItem[$arField['FIELD_NAME']] == '1' ? '1' : '' ?>"
                                                                <?= $arItem[$arField['FIELD_NAME']] == '1' ? 'checked' : '' ?>>
                                                        </div>
                                                    </td>
                                                    <?
                                                    $index++;
                                                    break;
                                            }
                                        } else {
                                            switch ($arField['FIELD_NAME']) {
                                                case 'INDEX':
                                                    ?>
                                                    <td><?= $i ?></td>
                                                    <?
                                                    $index++;
                                                    break;
                                                default:
                                                    break;
                                            }
                                        }
                                    } ?>
                                </tr>
                                <?
                                $i++;
                                if ($last_row) {?>
                                    <tr class="last_row">
                                    <?$clm = 1;
                                    foreach ($arResult['FIELDS'] as $arField) {
                                        if ($clm != 2) { ?>
                                        <td <?=$clm == 1 ? 'colspan="2"' : ''?> style="text-align: <?= $clm == 1 ? 'right' : 'left' ?>">
                                            <strong>
                                                <?$text = $arField['FIELD_NAME'] == 'INDEX' ? 'Итого:' : ' '?>
                                                <?=isset($arResult['SUM'][$arPeriod['ID']][$platform_id][$arField['FIELD_NAME']])
                                                    ?
                                                    $arResult['SUM'][$arPeriod['ID']][$platform_id][$arField['FIELD_NAME']]
                                                    :
                                                    $text;
                                                ?>
                                            </strong>
                                        </td>
                                            <?
                                            }
                                        $clm++;
                                    } ?>
                                    </tr>
                                <?
                                }
                            }

                            $index++;
                        } ?>
                        <tr class="total_sum-row">
                            <?
                            $i_fields = 1;
                            foreach ($arResult['FIELDS'] as $arField) {
                                if ($i_fields != 2) {?>
                                    <td <?=$i_fields == 1 ? 'colspan="2"' : ''?> style="text-align: <?= $i_fields == 1 ? 'right' : 'left' ?>">
                                        <strong>
                                            <?$text = $arField['FIELD_NAME'] == 'INDEX' ? 'Общий итог:' : ' '?>
                                            <?=isset($arResult['TOTAL_SUM'][$arPeriod['ID']][$arField['FIELD_NAME']])
                                                ?
                                                $arResult['TOTAL_SUM'][$arPeriod['ID']][$arField['FIELD_NAME']]
                                                :
                                                $text;
                                            ?>
                                        </strong>
                                    </td>
                                <?}
                                $i_fields++;
                            }?>
                        </tr>
                        </tbody>
                    </table>
                <? } ?>
            </div>
        </div>

    <?
    $period_index++;
    } ?>

</div>
<script>
    $(document).ready(function () {
        setHeadCellWidth();
    });
    $(window).on('resize', function() {
        setHeadCellWidth();
    });
    $(document).ready(function () {
        let initEdit = $('.js-init-field-change'),
            initPeriodEdit = $('.js-init-change-period'),
            initPeriodSave = $('.js-init-save-period'),
            initPeriodDelete = $('.js-init-delete-period'),
            initBtnHidden = $('.js-init-collapse'),
            initPeriodDefaultSet = $('.js-init-btn_default'),
            initPeriodCopy = $('.js-init-btn_default-copy');

        initPeriodCopy.on('click', function (e) {
            let parent = $(this).closest('.crm-lead-schedule-payment_head-default'),
                container = $(this).closest('.schedule-payments__period'),
                period_id = container.find('.schedule-payments__period-head').attr('data-period-id'),
                value = parent.find('select').val();

            $(this).addClass('ui-btn-clock');
            $.ajax({
                url: window.location,
                data: {
                    action: 'copy_period',
                    id: value,
                    url: window.location.href
                },
                method: 'POST',
                dataType: 'json',
                success: function (response) {
                    $(this).removeClass('ui-btn-clock');
                    alert(response['message']);
                    $.ajax({
                        url: window.location.href,
                        type: 'get',
                        data: {
                            action: 'update',
                            period_id: period_id
                        },
                        success: function (response) {

                            $('.crm-lead-schedule-payment-wrapper').parent().html(response);
                        }
                    });
                }
            })
        });

        initPeriodDefaultSet.on('click', function (e) {
            let parent = $(this).closest('.crm-lead-schedule-payment_head-default'),
                container = $(this).closest('.schedule-payments__period'),
                period_id = container.find('.schedule-payments__period-head').attr('data-period-id'),
                value = parent.find('select').val();

            $(this).addClass('ui-btn-clock');
            $.ajax({
                url: window.location,
                data: {
                    action: 'set_default',
                    id: value,
                    url: window.location.href
                },
                method: 'POST',
                dataType: 'json',
                success: function (response) {
                    $(this).removeClass('ui-btn-clock');
                    alert(response['message']);
                    $.ajax({
                        url: window.location.href,
                        type: 'get',
                        data: {
                            action: 'update',
                            period_id: period_id
                        },
                        success: function (response) {

                            $('.crm-lead-schedule-payment-wrapper').parent().html(response);
                        }
                    });
                }
            })
        });
        initBtnHidden.on('click', function (e) {
            let parent = $(this).closest('.schedule-payments__period');
            if ($(this).hasClass('btn-collapse')) {
                $('.btn-expand').each(function () {
                    $(this).removeClass('btn-expand').addClass('btn-collapse').find('span').text('Развернуть');
                });
                $('.table-expand').each(function () {
                    $(this).removeClass('table-expand').addClass('table-collapse');
                });

                $(this).removeClass('btn-collapse').addClass('btn-expand').find('span').text('Свернуть');
                if (parent.hasClass('table-collapse')) {
                    parent.removeClass('table-collapse').addClass('table-expand');
                }
            } else {

                $(this).removeClass('btn-expand').addClass('btn-collapse').find('span').text('Развернуть');
                if (parent.hasClass('table-expand')) {
                    parent.removeClass('table-expand').addClass('table-collapse');
                }
            }
        });
        initPeriodDelete.on('click', function (e) {
            let periodWrapper = $(this).closest('.schedule-payments__period'),
                headWrapper = $(this).closest('.schedule-payments__period-head'),
                period_id = headWrapper.attr('data-period-id'),
                data = {};

            if (confirm('Вы действительно хотите удалить данный период?')) {
                data['action'] = 'delete_period';
                data['period_id'] = period_id;


                $.ajax({
                    url: window.location,
                    data: data,
                    method: 'POST',
                    dataType: 'json',
                    success: function (response) {
                        periodWrapper.addClass('remove-period');
                        setTimeout(function () {
                            periodWrapper.remove();
                        }, 500);
                    }
                })
            }
        });
        initPeriodSave.on('click', function (e) {
            let headWrapper = $(this).closest('.schedule-payments__period-head'),
                period_id = headWrapper.attr('data-period-id'),
                send = false,
                data = {};

            headWrapper.find('.inp-edit').each(function () {
                send = true;
                data[$(this).attr('name')] = $(this).val();

            });

            if (send) {
                headWrapper.addClass('loading-save');
                data['action'] = 'update_period';
                data['period_id'] = period_id;
                data['url'] = window.location.href;


                $.ajax({
                    url: window.location,
                    data: data,
                    method: 'POST',
                    dataType: 'json',
                    success: function (response) {
                        if (response['result'] === true) {
                            $.ajax({
                                url: window.location.href,
                                type: 'get',
                                data: {
                                    action: 'update',
                                    period_id: period_id
                                },
                                success: function (response) {
                                    $('.crm-lead-schedule-payment-wrapper').parent().html(response);
                                    headWrapper.removeClass('loading-save');
                                }
                            });
                        } else {
                            $.each(response['message'], function (code, value) {
                                switch (code) {
                                    case 'system':
                                        alert(value);
                                        break;
                                    default:
                                        headWrapper.find('[data-name="'+code+'"]').append('<div class="hint error">'+value+'</div>');
                                }
                            });
                            headWrapper.removeClass('loading-save');
                        }

                    }
                })
            }


        });
        initPeriodEdit.on('click', function (e) {
            let btnWrapper = $(this).closest('.period-head__title-edit'),
                fieldsWrapper = $(this).closest('.period-head__title').find('.period-head__fields');

            btnWrapper.find('.btn-icon').each(function () {
                if ($(this).hasClass('btn-save') === false) {
                    $(this).css('display', 'none');
                } else {
                    $(this).css('display', 'block');
                }
            });

            fieldsWrapper.find('.period-field__group-value').each(function () {
                let fieldValue = $(this).attr('data-value'),
                    fieldType = $(this).attr('data-type'),
                    fieldName = $(this).attr('data-name');

                if (fieldName !== 'UF_BALANCE' && fieldName !== 'UF_BALANCE_FACT') {
                    $(this).empty();
                }
                switch (fieldType) {
                    case 'date':
                        $(this).append('<input class="inp-edit" type="date" name="' + fieldName + '" value="' + fieldValue + '">');
                        break;
                    case 'enumeration':
                        $(this).append('<select class="inp-edit" name="' + fieldName + '"></select>');

                        let values = JSON.parse($(this).attr('data-values')),
                            sel = $(this).find('select');

                        $.each(values, function (id, value) {
                            let checked = id === fieldValue ? 'selected' : '';
                            sel.append('<option value="' + id + '" ' + checked + '>' + value["VALUE"] + '</option>');
                        });


                        break;
                    case 'string':
                        if (fieldName !== 'UF_BALANCE' && fieldName !== 'UF_BALANCE_FACT') {
                            $(this).append('<input class="inp-edit" type="text" name="' + fieldName + '" value="' + fieldValue + '">');
                        }
                        break;

                }
            });
        });
        initEdit.on('click', function (e) {
            if ($(this).hasClass('init-edit') === false) {
                let fieldType = $(this).attr('data-type'),
                    fieldName = $(this).attr('data-name'),
                    fieldValue = $(this).attr('data-value'),
                    widthBl = $(this).innerWidth();

                $(this).addClass('init-edit').closest('td').css('width', widthBl + 'px');
                switch (fieldType) {
                    case 'string':
                        let vl = fieldValue == 0 ? '' : fieldValue;
                        $(this).empty().append('<input class="inp-edit" type="text" name="' + fieldName + '" value="' + vl + '">');

                        let inp = $(this).find('input'),
                            val = inp.val();
                        inp.css('width', widthBl + 'px');
                        inp.val('').focus().val(val);

                        break;
                    case 'enumeration':
                        $(this).empty().append('<select class="inp-edit" name="' + fieldName + '"></select>');

                        let values = JSON.parse($(this).attr('data-values')),
                            sel = $(this).find('select');

                        $.each(values, function (id, value) {
                            let checked = id === fieldValue ? 'selected' : '';
                            sel.append('<option value="' + id + '" ' + checked + '>' + value["VALUE"] + '</option>');
                        });
                        sel.focus();
                        break;
                    case 'boolean':
                        $(this).find('input').addClass('inp-edit');
                        break;
                }
            }
        });

        $(document).keypress(function (e) {
            if (e.which === 13) {
                checkAndSendData();
            }
        });
        $(document).mouseup(function (e) {
            let initEdit = $(".js-init-field-change");
            if (!initEdit.is(e.target) && initEdit.has(e.target).length === 0) {
               // checkAndSendData();
            }
        });
    });
    $(document).on("input",function(ev){
        let parent = $(ev.target).parent();

        if (parent.find('.error').length > 0 && $(ev.target).val().length > 0) {
            parent.find('.error').remove();
        }
    });
    function setHeadCellWidth() {
        $('.period-head__title').each(function () {
            let headWidth = $(this).innerWidth() - 50,
                countCell = 0;

            $(this).find('.period-field__group').each(function () {
                let group = $(this).find('.period-field__group-value');
                if (group.attr('data-type') === 'date') {
                    $(this).css('width', '133px');
                    headWidth = headWidth - 133;
                } else {
                    if (group.attr('data-name') === 'UF_BALANCE_FACT' || group.attr('data-name') === 'UF_BALANCE') {
                        $(this).css('width', '104px');
                        headWidth = headWidth - 104;
                    } else {
                        countCell++;
                    }
                }
                headWidth = headWidth - 10;
            });
            $(this).find('.period-field__group').each(function () {
                let group = $(this).find('.period-field__group-value');
                if (group.attr('data-type') !== 'date' && group.attr('data-name') !== 'UF_BALANCE_FACT' && group.attr('data-name') !== 'UF_BALANCE') {
                    $(this).css('width', headWidth / countCell + 'px');
                }

            });
        });
    }
    function checkAndSendData() {
        let table = $('.table-expand table'),
            body = table.closest('.schedule-payments__period-body'),
            period_id = $('.table-expand').find('.schedule-payments__period-head').attr('data-period-id'),
            data = {};

        if (table.hasClass('loading-save') === false) {
            let error = false,
                send = false;

            table.find('tr').each(function () {
                let isInput = false;
                $(this).find('.inp-edit').each(function () {
                    //let period_id = $(this).closest('.crm-lead-schedule-payment-wrapper').find('.schedule-payments__period-head').attr('data-period-id');
                    isInput = true;
                    val = $(this).val();
                    if ($(this).attr('name') !== 'UF_COMMENT') {
                        if (val.length <= 0) {
                            $(this).parent().append('<div class="hint error">Поле не может быть пустым</div>');
                            error = true;
                        }
                        if (parseFloat(val) < 0) {
                            $(this).parent().append('<div class="hint error">Значение не может быть отрицательным</div>');
                            error = true;
                        }
                    }

                });

                if (error === false && isInput === true) {
                    let row = {};

                    $(this).find('.inp-edit').each(function () {
                        if ($(this).val().length > 0) {
                            send = true;
                            $(this).blur();
                            row[$(this).attr('name')] = $(this).val();
                        }
                    });
                    if (send === true && typeof row !== 'undefined') {
                        let row_id = $(this).attr('data-row-id');
                        if (typeof row_id !== 'undefined') {
                            data[$(this).attr('data-row-id')] = row;
                        }
                    }
                }
            });

            if (send === true) {
                $('.table-expand table').closest('table').addClass('loading-save');
                data['action'] = 'save';
                data['period_id'] = period_id;
                data['url'] = window.location.href;
                $.ajax({
                    url: location.window,
                    data: data,
                    method: 'POST',
                    dataType: 'json',
                    success: function (response) {

                        if (response['result'] === true) {
                            $.ajax({
                                url: window.location.href,
                                type: 'get',
                                data: {
                                    action: 'update',
                                    period_id: $('.table-expand').find('.schedule-payments__period-head').attr('data-period-id')
                                },
                                success: function (response) {
                                    table.removeClass('loading-save');
                                    $('.crm-lead-schedule-payment-wrapper').parent().html(response);
                                }
                            });
                        } else {
                            table.removeClass('loading-save');
                            $.each(response['message'], function (code, value) {
                                switch (code) {
                                    case 'system':
                                        alert(value);
                                        break;
                                }
                            });
                        }
                    }
                })
            }
        }
    }
</script>
<div style="display: none">
    <div class="box-modal">
        <div class="box-modal_close arcticmodal-close"><span class="crm-item-del">×</span></div>
        <div class="box-modal_wrapper">
            <h3 class="box-modal_title">Добавление нового периода</h3>
            <div class="box-modal_content">
                <form class="box-modal_form" id="add_new_element">
                    <input type="hidden" value="<?= $arResult['ID'] ?>" name="UF_GROUP_ID">
                    <input type="hidden" value="create_period" name="action">
                    <?= bitrix_sessid_post() ?>
                    <? foreach ($arResult['PERIOD_FIELDS'] as &$arField) {
						switch ($arField['FIELD_NAME']) {
							case 'UF_GROUP_ID':
							case 'UF_PAYMENT_TYPE':
							case 'UF_BALANCE':
							case 'UF_BALANCE_FACT':
							case 'UF_PAYMENT_DATE':
							case 'UF_CLIENT_PAY':
							case 'UF_PAYMENT_FACT':
							case 'UF_DEFAULT':
							case 'UF_AUTO_RENEWAL':
								continue;
								break;
							default:
							?>
							<div class="group-field">
                            <label for="<?= $arField['FIELD_NAME'] ?>"><?= $arField['LIST_COLUMN_LABEL'] ?></label>

                            <? switch ($arField['USER_TYPE_ID']) {
                                case 'string':
                                    ?>
                                    <input id="<?= $arField['FIELD_NAME'] ?>"
                                           class="crm-entity-widget-content-input inp"
                                           type="text"
                                           name="<?= $arField['FIELD_NAME'] ?>">
                                    <?
                                    break;
                                case 'date':
                                    ?>
                                    <input id="<?= $arField['FIELD_NAME'] ?>"
                                           class="crm-entity-widget-content-input inp"
                                           type="date"
                                           name="<?= $arField['FIELD_NAME'] ?>">
                                    <?
                                    break;
                                case 'enumeration':
                                    ?>
                                    <select id="<?= $arField['FIELD_NAME'] ?>"
                                            class="crm-entity-widget-content-input inp"
                                            name="<?= $arField['FIELD_NAME'] ?>">
                                        <?
                                        foreach ($arField['VALUES'] as $arValue) { ?>
                                            <option value="<?= $arValue['ID'] ?>" <?=$arValue['DEF'] == 'Y' ? 'selected' : ''?>>
                                                <?= $arValue['VALUE'] ?>
                                            </option>
                                            <?
                                        } ?>
                                    </select>
                                    <?
                                    break;
                            } ?>
                        </div>
							<?
						
						}
                        ?>
                        
                    <? } ?>

                    <div class="group-btn">
                        <a class="ui-btn ui-btn-success js-init-btn" data-action="create_period">Добавить</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        let initBtn = $('.js-init-btn');

        initBtn.on("click", function (e) {
            e.preventDefault();
            $(this).addClass('ui-btn-clock');

            let action = $(this).attr('data-action') || '',
                row = $(this).closest('tr'),
                item_id = row.attr('data-item-id'),
                form = $('#add_new_element');

            switch (action) {
                case 'create_period':
                    $.ajax({
                        url: window.location.href,
                        type: 'get',
                        data: form.serialize(),
                        success: function (response) {
                            $(this).removeClass('ui-btn-clock');
                            $.arcticmodal('close');
                            $.ajax({
                                url: window.location.href,
                                type: 'get',
                                data: {
                                    action: 'update'
                                },
                                success: function (response) {
                                    $('.crm-lead-schedule-payment-wrapper').parent().html(response);
                                }
                            });
                        }
                    });
                    break;
                case 'show_modal':
                    $('.box-modal').arcticmodal();
                    $(this).removeClass('ui-btn-clock');
                    break;
                case 'delete':
                    if (confirm('Вы точно хотите удалить запись?')) {
                        $.ajax({
                            url: window.location.href,
                            type: 'get',
                            data: {
                                action: action,
                                id: item_id
                            },
                            success: function (response) {
                                $(this).removeClass('ui-btn-clock');
                            }
                        });
                        $.ajax({
                            url: window.location.href,
                            type: 'get',
                            data: {
                                action: 'update'
                            },
                            success: function (response) {
                                $('.crm-lead-schedule-payment-wrapper').parent().html(response);
                            }
                        });
                    }
                    break;
                case 'edit':
                    row.find('.h-hide').each(function () {
                        $(this).css('display', 'block');
                    });
                    row.find('.h-show').each(function () {
                        $(this).css('display', 'none');
                    });

                    break;
                case 'save':
                    let data = {
                        action: 'save',
                        id: item_id
                    };
                    row.find('select, input').each(function () {
                        data[$(this).attr('name')] = $(this).val();
                    });

                    $.ajax({
                        url: window.location,
                        type: 'get',
                        data: data,
                        success: function (response) {
                            if (typeof response !== 'undefined') {
                                $.ajax({
                                    url: window.location.href,
                                    type: 'get',
                                    data: {
                                        action: 'update'
                                    },
                                    success: function (response) {
                                        $(this).removeClass('ui-btn-clock');
                                        $('.crm-lead-schedule-payment-wrapper').parent().html(response);
                                    }
                                });
                            }
                        }
                    });
                    break;
                case 'get_contract':
                    break;
            }
            $(this).removeClass('ui-btn-clock');
            return false;
        });
    });
</script>