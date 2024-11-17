# AWZ: Курсы валют НБРБ, ЦБРФ (awz.currency)

### [Установка модуля](https://github.com/zahalski/awz.currency/tree/main/docs/install.md)

<!-- desc-start -->

Модуль для загрузки курсов валют с НБРБ и ЦБРФ. 
Модуль может автоматически обновлять курсы валют для модуля currency, а также хранит историю курсов в базе данных.

**Возможности модуля:**<br>
* Опционально может загружать курсы в модуль currency согласно базовой валюты сайта.
* Возможность одновременного использования нескольких провайдеров курсов.
* Ежедневное обновление курса на агенте.
* Api для конверсии сумм из одной валюты в другую.

Автоматически выберет провайдер курсов в зависимости от настроек базовой валюты:
* базовая BYN - НБРБ
* базовая RUB - ЦБРФ

**Поддерживаемые редакции CMS Битрикс:**<br>
«Старт», «Стандарт», «Малый бизнес», «Бизнес», «Корпоративный портал», «Энтерпрайз», «Интернет-магазин + CRM»

<!-- desc-end -->

<!-- dev-start -->

### Awz\Currency\CursTable::getCurs

<em>Возвращает курсы на дату</em>

| Параметр           |                    | Описание                  |
|--------------------|--------------------|---------------------------|
| $date `string`     | Обязательно        | Дата курса для strtotime  |
| $provider `string` | По умолчанию, cbrf | Провайдер cbrf или nbrb   |
| $param `string`    | По умолчанию, day  | Пока только поддержка day |

Возвращает массив с курсами `array`

```
[
    'BYN'=>[
        ['ID'] => 21,
        ['CODE'] => BYN,
        ['PROVIDER'] => cbrf,
        ['AMOUNT'] => 29.6279,
        ['AMOUNT_CNT'] => 1,
        ['CURS_DATE'] => 'Bitrix\Main\Type\DateTime Object'
    ],
    'CNY'=>[
        ['ID'] => 24,
        ['CODE'] => CNY,
        ['PROVIDER'] => cbrf,
        ['AMOUNT'] => 13.7992,
        ['AMOUNT_CNT'] => 1,
        ['CURS_DATE'] => 'Bitrix\Main\Type\DateTime Object'
    ]
]
```

### Awz\Currency\CursTable::convert

<em>Конвертирует сумму из одной валюты в другую</em>

| Параметр           |                    | Описание                    |
|--------------------|--------------------|-----------------------------|
| $cFrom `string`    | Обязательно        | код валюты откуда конверсия |
| $cTo `string`      | Обязательно        | код валюты куда конверсия   |
| $summ `float`      | Обязательно        | сумма                       |
| $date `string`     | Обязательно        | Дата курса для strtotime    |
| $provider `string` | По умолчанию, cbrf | Провайдер cbrf или nbrb     |

Возвращает объект `\Bitrix\Main\Result` с результатом конверсии

```php
use Bitrix\Main\Loader;
use Bitrix\Main\CursTable;

if(Loader::includeModule('awz.currency')){
    $convertResult = \Awz\Currency\CursTable::convert(
        'USD', 'RUB', 100, '01.02.2023'
    );
    if($convertResult->isSuccess()){
        $convertData = $convertResult->getData();
        print_r($convertData);
    }
}

```

```php
/*
* Array
*  (
*      [result] => 7051.74
*      [curs_from] => Array
*          (
*              [ID] => 38
*              [CODE] => USD
*              [PROVIDER] => cbrf
*              [AMOUNT] => 70.5174
*              [AMOUNT_CNT] => 1
*              [CURS_DATE] => Bitrix\Main\Type\DateTime Object
*                  (
*                      [value:protected] => DateTime Object
*                          (
*                              [date] => 2023-02-01 00:00:00.000000
*                              [timezone_type] => 3
*                              [timezone] => Europe/Moscow
*                          )
*  
*                      [userTimeEnabled:protected] => 1
*                  )
*  
*          )
*  
*      [curs_to] => Array
*          (
*              [AMOUNT_CNT] => 1
*              [AMOUNT] => 1
*          )
*  
*  )
* */
```

<!-- dev-end -->

<!-- cl-start -->
## История версий

https://github.com/zahalski/awz.admin/blob/master/CHANGELOG.md

<!-- cl-end -->