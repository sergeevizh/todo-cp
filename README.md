todo-cp
=======

ToDo list for CasePress
Основана на http://johnny.github.io/jquery-sortable/


# Todo
- за основу берем плагин https://github.com/systemo-biz/todo-comments-cp - он уже умеет 80% того что нужно
- меняем имя шорткода и прочие ключи, чтобы плагины не пересекались и могли работать в рамках одной страницы
- убираем галочку "на контроле"
- реализуем поле добавления пункта в список (пользователь может добавить пункт в список) как обычно это делается в чек листах
- пользователь может добавить несколько пунктов и упорядочить их по иерархии
- при закрытии чек поинта должна проставляться дата закрытия в мету done_time_item_cp и дата закрытия должна показываться в тексте чек поинта
- опция "Скрыть/показать закрытые пункт", при нажатии скрывает или показывает закрытые пункты
- опция "Показать историю закрытий" выводит порядок закрытий через thikbox по урл типа (post?view=list_done_history) (пример логики https://github.com/systemo-biz/casepress/blob/master/cp-includes/cases/includes/visits/visits_check/view.php )
