<?php

namespace Awz\Currency;

use Bitrix\Main\Loader;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

/**
 * Обработчики событий сторонних модулей.
 *
 * Интеграция с awz.allxls (пошаговый импорт XLSX/CSV): макросы курсов валют
 * в правилах сопоставления колонок.
 *
 * Формат макросов (по всем курсам из настроек модуля — таблица b_awz_currency):
 *   #AWZ_CURRENCY_NBRB_USD#  — курс USD от НБРБ (за 1 единицу валюты, BYN)
 *   #AWZ_CURRENCY_CBRF_USD#  — курс USD от ЦБРФ (за 1 единицу валюты, RUB)
 *
 * Регистрация обработчиков — в install/index.php (InstallEvents):
 *   EventManager: ('awz.allxls', 'onAwzAllxlsGetMacros')    -> Handlers::onAwzAllxlsGetMacros
 *   EventManager: ('awz.allxls', 'onAwzAllxlsExpandMacros') -> Handlers::onAwzAllxlsExpandMacros
 *
 * Значение курса нормализуется к 1 единице валюты: AMOUNT / AMOUNT_CNT
 * (для USD НБРБ это курс в BYN за 1 USD, для ЦБРФ — в RUB за 1 USD).
 * Курс берется на текущую дату (если на дату курса нет и включена опция
 * LOAD_PREW, берется последний доступный — штатная логика CursTable::getCurs).
 */
class Handlers
{
    /** @var string код модуля */
    const MODULE = 'awz.currency';

    /** @var array провайдеры курса: код в макросе => провайдер в CursTable */
    protected static array $providers = [
        'NBRB' => 'nbrb',
        'CBRF' => 'cbrf',
    ];

    /** @var array кеш списка кодов валют (на процесс) */
    protected static ?array $codesCache = null;

    /** @var array кеш курсов на процесс: провайдер => список курсов CursTable::getCurs */
    protected static array $cursCache = [];

    /**
     * Событие onAwzAllxlsGetMacros (модуль awz.allxls):
     * добавляем в список макросов диалога правил все доступные курсы
     * (#AWZ_CURRENCY_<ПРОВАЙДЕР>_<КОД>#).
     *
     * @param Event $event
     * @return void
     */
    public static function onAwzAllxlsGetMacros(Event $event): void
    {

        $codes = self::getCurrencyCodes();
        if (empty($codes)) {
            return;
        }
        $macros = [];
        foreach ($codes as $providerCode => $codes2) {
            foreach ($codes2 as $code) {
                $title =  mb_strtoupper($providerCode) === 'NBRB' ? 'НБРБ' : 'ЦБРФ';
                $macros['#AWZ_CURRENCY_' . mb_strtoupper($providerCode) . '_' . $code . '#'] =
                    'Курс ' . $code . ' (' . $title . ')';
            }
        }
        $event->addResult(new EventResult(
            EventResult::SUCCESS,
            ['macros' => $macros]
        ));
    }

    /**
     * Событие onAwzAllxlsExpandMacros (модуль awz.allxls):
     * раскрываем макросы #AWZ_CURRENCY_<ПРОВАЙДЕР>_<КОД># значениями курсов
     * на текущую дату.
     *
     * @param Event $event
     * @return void
     */
    public static function onAwzAllxlsExpandMacros(Event $event): void
    {
        $str = (string)$event->getParameter('str');
        if ($str === '' || mb_strpos($str, '#AWZ_CURRENCY_') === false) {
            return;
        }
        $newStr = preg_replace_callback(
            '/#AWZ_CURRENCY_(NBRB|CBRF)_([A-Z0-9]{2,5})#/',
            function ($m) {
                $provider = self::$providers[$m[1]] ?? '';
                return $provider !== '' ? self::getCursValue($provider, $m[2]) : '';
            },
            $str
        );
        if ($newStr !== $str) {
            $event->setParameter('str', $newStr);
            $event->addResult(new EventResult(EventResult::SUCCESS, []));
        }
    }

    /**
     * Курс валюты за 1 единицу (AMOUNT / AMOUNT_CNT) на текущую дату.
     * Курс загружается штатными агентами модуля (или лениво — CursTable::getCurs
     * сам вызывает updateCurs при отсутствии данных на дату).
     *
     * @param string $provider провайдер ('nbrb'|'cbrf')
     * @param string $code код валюты (USD, EUR, ...)
     * @return string пустая строка, если курс не найден
     */
    protected static function getCursValue(string $provider, string $code): string
    {
        try {
            if (!isset(self::$cursCache[$provider])) {
                if (!Loader::includeModule(self::MODULE)) {
                    return '';
                }
                self::$cursCache[$provider] = CursTable::getCurs(date('d.m.Y'), $provider);
            }
            $data = self::$cursCache[$provider][$code] ?? null;
            if (!$data || !isset($data['AMOUNT'])) {
                return '';
            }
            $cnt = max(1, (int)($data['AMOUNT_CNT'] ?? 1));
            return (string)round((float)$data['AMOUNT'] / $cnt, 4);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Коды валют, загруженные модулем (по всем провайдерам, distinct).
     *
     * @return array
     */
    protected static function getCurrencyCodes(): array
    {
        if (self::$codesCache !== null) {
            return self::$codesCache;
        }
        self::$codesCache = [];
        try {
            if (!Loader::includeModule(self::MODULE)) {
                return self::$codesCache;
            }
            $res = CursTable::getList([
                'select' => ['CODE', 'PROVIDER'],
                'group' => ['CODE', 'PROVIDER'],
                'order' => ['PROVIDER'=>'ASC','CODE' => 'ASC'],
            ]);
            while ($row = $res->fetch()) {
                if ((string)$row['CODE'] !== '') {
                    self::$codesCache[$row['PROVIDER']] = self::$codesCache[$row['PROVIDER']] ?? [];
                    self::$codesCache[$row['PROVIDER']][] = (string)$row['CODE'];
                }
            }
        } catch (\Throwable $e) {
            self::$codesCache = [];
        }
        return self::$codesCache;
    }
}