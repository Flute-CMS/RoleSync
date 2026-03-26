# RoleSync

Роли на сайте (VIP в чате, доступ в разделы) можно выдавать и снимать автоматически по тому, что известно о игроке на сервере через **GiveCore**: есть ли VIP в игре, набран ли порог опыта и т.д. Правило звучит простым языком: «если выполняется набор условий — выдать роль X», причём условия можно объединять «и» внутри группы и «или» между группами, чтобы не плодить десяток почти одинаковых правил.

Синхронизация может прогоняться при входе пользователя; в админке видны правила и журнал, если что-то пошло не так.

Без **GiveCore 2.x** этот модуль по смыслу не раскрывается: он как раз связывает сайт с проверками GiveCore.

---

Flute site roles (VIP areas, chat access, etc.) can follow what **GiveCore** knows about a player—VIP on a server, XP thresholds, and similar checks. You write rules in plain terms: when a bundle of conditions matches, grant role X. You can AND conditions inside a group and OR groups so you do not maintain ten nearly identical rules.

Sync can run on login; the admin UI shows rules and logs when something fails.

You need **GiveCore 2.x**; the module exists to bridge GiveCore checks into Flute roles.

## Installation

Download the latest release and install it via the Flute CMS admin panel.

Current version: **1.0.0**

## Authors

- [object Object]

## Links

- [Flute CMS](https://flute-cms.com)
- [Module page](https://flute-cms.com/market/rolesync)
