<?php

namespace Ometra\AetherClient\Console\Commands;

final class Frequency
{
    public const TYPES = [
        'minutes' => 'En minutos',
        'cron'    => 'Expresión CRON',
    ];

    public const CRON_OPTIONS = [
        'every_minute'         => 'Cada minuto',
        'every_five_minutes'  => 'Cada 5 minutos',
        'every_ten_minutes'   => 'Cada 10 minutos',
        'every_thirty_minutes'=> 'Cada 30 minutos',
        'hourly'              => 'Cada hora',
        'daily'               => 'Todos los días a la medianoche',
        'weekly'              => 'Todos los domingos a la medianoche',
        'monthly'             => 'El primer día de cada mes a la medianoche',
    ];

    public const CRON_MAP = [
        'every_minute'         => '* * * * *',
        'every_five_minutes'  => '*/5 * * * *',
        'every_ten_minutes'   => '*/10 * * * *',
        'every_thirty_minutes'=> '*/30 * * * *',
        'hourly'              => '0 * * * *',
        'daily'               => '0 0 * * *',
        'weekly'              => '0 0 * * 0',
        'monthly'             => '0 0 1 * *',
    ];

    public static function cron(string $key): string
    {
        return self::CRON_MAP[$key];
    }
}
