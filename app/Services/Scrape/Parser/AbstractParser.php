<?php
declare(strict_types=1);

namespace App\Services\Scrape\Parser;

use App\Services\Scrape\ValueObject\ParsedResult\ProductName;
use Exception;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractParser
{
    protected string $selectorProductTitleText = '';
    protected string $selectorProductTitleOption = '';
    protected string $selectorProductNameText = '';
    protected string $selectorProductNameOption = '';
    protected string $selectorProductDescription = '';

    protected Crawler|null $crawler = null;

    /**
     * Конструктор класса
     * @param Crawler|string|null $html Сырые данные или Crawler
     * @return AbstractParser
     */
    public static function create(Crawler|string|null $html = null): static
    {
        $static = new static();

        if ($html) {
            $static->setCrawler($html);
        }

        return $static;
    }

    /**
     * Возвращает Crawler или выбрасывает исключение
     * @return Crawler
     * @throws Exception
     */
    public function getCrawler(): Crawler
    {
        if ($this->crawler === null) {
            throw new Exception('Не установлены данные для парсинга');
        }

        return $this->crawler;
    }

    /**
     * Устанавливает данные или Crawler
     * @param Crawler|string $html
     * @return $this
     */
    public function setCrawler(Crawler|string $html): self
    {
        $this->crawler = ($html instanceof Crawler ? $html : new Crawler($html));

        return $this;
    }

    /**
     * Возвращает текст по указанному селектору
     * @param string $selector
     * @return string|null
     */
    protected function getTextBySelector(string $selector): ?string
    {
        $value = $this->getCrawler()->filter($selector)->text('');

        return ($value !== '' ? trim($value, ' ') : null);
    }

    /**
     * Возвращает заголовок товара
     * @return ProductName
     */
    public function getProductTitle(): ProductName
    {
        return new ProductName([
            'text' => $this->getTextBySelector($this->selectorProductTitleText),
            'option' => $this->getTextBySelector($this->selectorProductTitleOption),
        ]);
    }

    /**
     * Возвращает название товара
     * @return ProductName
     */
    public function getProductName(): ProductName
    {
        return new ProductName([
            'text' => $this->getTextBySelector($this->selectorProductNameText),
            'option' => $this->getTextBySelector($this->selectorProductNameOption),
        ]);
    }

    /**
     * Возвращает собранные данные в виде массива
     * @return array
     */
    public function getData(): array
    {
        return [];
    }
}
