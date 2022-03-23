<?php
declare(strict_types=1);

namespace App\Services\Scrape\Parser;

class Computers extends AbstractParser
{
    protected string $selectorProductNameText = '.page-title > .h1';
    protected string $selectorProductNameOption = '.page-title > .h1 span';

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        return [
            'name' => $this->getProductName()->getData(),
        ];
    }
}
