<?php
declare(strict_types=1);

namespace App\Services\Scrape;

use App\Services\Scrape\Parser\AbstractParser;
use App\Services\Scrape\Parser\Computers;

class ParserFactory
{
    protected static array $mapping = [
        Computers::class => [
            169,
        ],
    ];

    /**
     * @param int $id
     * @return AbstractParser|null
     */
    public static function getByCategoryId(int $id): ?AbstractParser
    {
        foreach (self::$mapping as $class => $keys) {
            if (in_array($id, $keys)) {
                return new $class();
            }
        }

        return null;
    }
}
