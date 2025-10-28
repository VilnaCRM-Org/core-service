<?php

declare(strict_types=1);

namespace App\Tests\Behat\CustomerContext\Mapper;

use DateInterval;
use DateTime;

final readonly class DateUrlMapper
{
    private const string PATTERN = '/!%date\((.*?)\),date_interval\((.*?)\)!%/';

    public function map(string $url): string
    {
        if (! preg_match_all(self::PATTERN, $url, $matches, PREG_SET_ORDER)) {
            return $url;
        }

        return array_reduce(
            $matches,
            fn (string $currentUrl, array $match) => $this->replaceDatePlaceholder($currentUrl, $match),
            $url
        );
    }

    /**
     * @param array<string> $match
     */
    private function replaceDatePlaceholder(string $url, array $match): string
    {
        [$fullMatch, $dateFormat, $intervalString] = $match;
        $date = $this->calculateDate($intervalString);
        $formattedDate = $date->format($dateFormat);

        return str_replace($fullMatch, $formattedDate, $url);
    }

    private function calculateDate(string $interval): DateTime
    {
        $date = new DateTime();
        $isNegative = str_starts_with($interval, '-');
        $intervalString = $isNegative ? substr($interval, 1) : $interval;
        $dateInterval = new DateInterval($intervalString);

        return $isNegative
            ? $date->sub($dateInterval)
            : $date->add($dateInterval);
    }
}
