# Первый запуск
Для корректной работы необходимо прописать в консоль следующую команду:
`php artisan migrate:fresh --seed`

Для отправки почты использовался сервис [MailTrap](https://mailtrap.io "MailTrap")
В файле **.env** заполните свои данные из [MailTrap](https://mailtrap.io "MailTrap")

    MAIL_DRIVER=smtp
    MAIL_HOST=smtp.mailtrap.io
    MAIL_PORT=2525
    MAIL_USERNAME={USERNAME}
    MAIL_PASSWORD={PASSWORD}
    MAIL_ENCRYPTION=tls


## API
Для распознавания пользователей в приложении используется **API TOKEN**
Для корректной работы необходимо в заголовках отправлять следующее:

`Authorization: Bearer {API_TOKEN}`

После запуска первого запуска приложение сгенерирует 5 пользователей, 10 оборудований, и 50 складов

Тестовый API TOKEN:
`a94a8fe5ccb19ba61c4c0873d391e987982fbbd3`

------------

`[POST]  /api/equipment/new`
- title - Наименование оборудования
- price - Стоимость
- serial_number - Серийный номер
- inventory_number - Инвентарный номер

------------

`[PUT] /api/equipment/update`
- id - ID оборудования
- warehouse - ID склада

------------

`[GET] /api/equipment/get`
- filter - Фильтр
Доступные значения: **date**, **price**, **status**
- asc - Фильтрация по возрастанию
Доступные значения: **true** / **false**
- q - Строка поиска
- type_search - Фильтр поиска
Доступные значения: **title**, **serial_number**, **inventory_number**, **status (Только для управляющего)**

