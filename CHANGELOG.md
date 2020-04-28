# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][keepachangelog] and this project adheres to [Semantic Versioning][semver].

> Правила ведения файла `CHANGELOG.md`:
>
> - Не опубликованные изменения "накапливаются" в секции `## Unreleased`
> - Описание каждого релиза начинается с секции вида `## vX.X.X` _(`%major%.%minor%.%patch%`)_, и содержит в себе 5 опциональных секций _(но не меньше одной на релиз)_:
>   - `### Added` - Добавленный функционал
>   - `### Fixed` - Исправления
>   - `### Deprecated` - Функционал, помеченный как устаревший и подлежащий удалению в скором времени
>   - `### Removed` - То, что было удалено
>   - `### Changed` - Изменения, явно не связанные с предыдущими тремя секциями
> - Секция релиза **может** сопровождаться датой релиза и иметь вид `## vX.X.X - YYYY-MM-DD`, но выставляться она должна непосредственно перед релизом
> - Присвоение значения новой версии должно соответствовать [правилам семантического версионирования][semver], за исключениями:
>   - Причиной поднятия **мажорного** значения версии являются наличие изменений, обратно не совместимых с теми, что в данный момент находятся на продуктовых серверах ("мягкий" откат невозможен). Это могут быть - **миграции**, изменения неймспейсов классов подлежащих **сериализации** в процессе работы приложения, изменения мажорной версии фреймворка, появление зависимости от какого-либо внешнего сервиса, и любые другие "опасные" изменения
> - Все ссылки должны оформляться в виде сносок `[LINK]:https://...` и располагались строго в блоке релиза
> - Каждая запись **должна** завершаться указанием ссылки на того, кто внёс те или иные изменения


## v0.0.7 - 2020-04-28

### Fix

- Limit to 2 decimal places in field payment.plan in schedule.payments

### Added

- Add new field Credit.Balance in schedule.payments

## v0.0.6 - 2020-04-28

### Fix

- Round payment.plan in schedule payments

## v0.0.5 - 2020-04-28

### Added

- When period copied, new period has a blue background

## v0.0.4 - 2020-04-28

### Fixed

- Fix birthday chat type (change to closed)

## v0.0.3 - 2020-04-18

### Added

- Added attach lead chat to deal

## v0.0.2 - 2020-04-18

### Added

- Added attach entity in task out entity in chat

### Fixed

- Fix name function in B24Tech/TaskHandler 
- Fix add chat for birthday worker personal
- Fix execute calculate rule for schedule payments

## v0.0.1 - 2020-04-15

### Added

- Added function CreateHappyBirthdayChat() in /local/php_interface/agents.php ([@RogSC])

### Fixed

- Fix b24tech:schedule.payments - PaymentFact+Credit always < PaymentPlan ([@RogSC])