<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Идентификация и брендинг завода (для тиража на 8 заводов комбината)
    |--------------------------------------------------------------------------
    | На каждом заводе — своя копия с собственным .env. Все значения ниже
    | переопределяются переменными LIMS_PLANT_* / LIMS_COMPANY_* / LIMS_LAB_* /
    | LIMS_ACCRED_*, поэтому один и тот же код обслуживает любой из 8 заводов.
    | Эти данные попадают во фронтенд (окно входа, шапки) и в PDF-паспорт качества.
    */
    'plant' => [
        'code'       => env('LIMS_PLANT_CODE', 'GMZ-3'),
        'name'       => [
            'ru' => env('LIMS_PLANT_NAME_RU', '5-й гидрометаллургический завод'),
            'uz' => env('LIMS_PLANT_NAME_UZ', '5-Gidrometallurgiya zavodi'),
            'en' => env('LIMS_PLANT_NAME_EN', '5th Hydrometallurgical Plant'),
        ],
        'company'    => [
            'ru' => env('LIMS_COMPANY_RU', 'АО «Навоийский горно-металлургический комбинат»'),
            'uz' => env('LIMS_COMPANY_UZ', '«Navoiy kon-metallurgiya kombinati» AJ'),
            'en' => env('LIMS_COMPANY_EN', 'Navoi Mining & Metallurgical Company JSC'),
        ],
        'company_short' => [
            'ru' => env('LIMS_COMPANY_SHORT_RU', 'АО «НГМК»'),
            'uz' => env('LIMS_COMPANY_SHORT_UZ', '«NKMK» AJ'),
            'en' => env('LIMS_COMPANY_SHORT_EN', 'NMMC JSC'),
        ],
        'lab_name'   => [
            'ru' => env('LIMS_LAB_NAME_RU', 'Центральная научно-исследовательская лаборатория'),
            'uz' => env('LIMS_LAB_NAME_UZ', 'Markaziy ilmiy-tadqiqot laboratoriyasi'),
            'en' => env('LIMS_LAB_NAME_EN', 'Central Research Laboratory'),
        ],
        'city'       => env('LIMS_PLANT_CITY', 'Навоий'),
        'logo'       => env('LIMS_PLANT_LOGO', '/ngmk.png'),
        // Данные об аккредитации (для шапки паспорта качества / COA).
        'accreditation' => [
            'number' => env('LIMS_ACCRED_NUMBER', ''),   // № аттестата аккредитации
            'valid'  => env('LIMS_ACCRED_VALID', ''),    // срок действия
            'body'   => env('LIMS_ACCRED_BODY', ''),     // орган по аккредитации
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Формат шифра пробы (объекта испытания)
    |--------------------------------------------------------------------------
    | Шифр присваивается автоматически при регистрации пробы.
    | Шаблон собирается из плейсхолдеров:
    |   {prefix} {year} {month} {seq}
    | seq — порядковый номер, обнуляемый согласно 'reset' (month|year|never),
    | дополняется нулями до seq_pad знаков.
    | Формат настраиваемый — на каждом заводе/лаборатории может отличаться;
    | переопределяется через .env (LIMS_SAMPLE_*).
    */
    'sample_code' => [
        'prefix'  => env('LIMS_SAMPLE_PREFIX', 'TL'),
        'format'  => env('LIMS_SAMPLE_FORMAT', '{prefix}-{year}-{month}-{seq}'),
        'seq_pad' => (int) env('LIMS_SAMPLE_SEQ_PAD', 4),
        'reset'   => env('LIMS_SAMPLE_RESET', 'month'), // month | year | never
    ],

    /*
    | Формат номера паспорта качества. Плейсхолдеры: {prefix} {year} {month} {seq}.
    */
    'cert_number' => [
        'prefix'  => env('LIMS_CERT_PREFIX', 'PS'),
        'format'  => env('LIMS_CERT_FORMAT', '{prefix}-{year}-{seq}'),
        'seq_pad' => (int) env('LIMS_CERT_SEQ_PAD', 4),
        'reset'   => env('LIMS_CERT_RESET', 'year'), // month | year | never
    ],

    /*
    |--------------------------------------------------------------------------
    | ИИ-помощник (анализ трендов вода/воздух)
    |--------------------------------------------------------------------------
    | Эвристическая оценка тренда работает всегда (без интернета).
    | Если задан relay_url — дополнительно вызывается ИИ через релей на ПК
    | программиста (OpenAI-совместимый API, напр. DeepSeek). Сервер завода без
    | интернета обращается к релею, релей — к ИИ. См. [[dispatch-ai-relay]].
    | Пусто — ИИ пропускается, показывается только эвристика.
    */
    'ai' => [
        'relay_url' => env('LIMS_AI_RELAY_URL', ''),          // напр. http://192.168.x.x:PORT/v1/chat/completions
        'model'     => env('LIMS_AI_MODEL', 'deepseek-chat'),
        'api_key'   => env('LIMS_AI_KEY', ''),
        'timeout'   => (int) env('LIMS_AI_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Пороги оповещений (центр оповещений лаборатории)
    |--------------------------------------------------------------------------
    | tat_overdue_days — проба «просрочена», если в работе дольше N дней.
    | calibration_warn_days — за сколько дней предупреждать о поверке приборов.
    */
    'tat_overdue_days'      => (int) env('LIMS_TAT_OVERDUE_DAYS', 3),
    'calibration_warn_days' => (int) env('LIMS_CALIBRATION_WARN_DAYS', 30),
];
