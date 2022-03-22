<?php
declare(strict_types=1);

namespace App\Services\Scrape\ValueObject\ParsedResult;

class ProductName extends AbstractParsedResult
{
    protected array $structure = [
        'text' => null,
        'option' => null,
    ];
}
