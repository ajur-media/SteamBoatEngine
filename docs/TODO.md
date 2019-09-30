# 1.10

## SBEngine::getContentURL()

Переименовать в `getContentWebPath` (или как-то похожее, потому что
функция возвращает путь к элементу контента, а не реальный WEB PATH. 

В этом пути не учитывается `$CONFIG['domains']['storage']['default']` или подобное.




 

