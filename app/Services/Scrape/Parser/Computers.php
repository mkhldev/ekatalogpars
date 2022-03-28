<?php
declare(strict_types=1);

namespace App\Services\Scrape\Parser;

use App\Services\Scrape\EKatalogParser;

class Computers extends AbstractParser
{
    protected string $selectorProductTitleText = '#top-page-title > h1';
    protected string $selectorProductTitleOption = '#top-page-title > h1 > span';
    protected string $selectorProductNameText = 'div.cont-block-title.no-mobile > span';
    protected string $selectorProductNameOption = 'div.cont-block-title.no-mobile > span > span';
    protected string $selectorProductDescription = '#eski-i-other-txt-1';

    protected EKatalogParser|null $parser = null;

    /**
     * Возвращает парсер
     * @return EKatalogParser
     */
    protected function getParser(): EKatalogParser
    {
        if (!$this->parser) {
            $this->parser = new EKatalogParser();
        }

        return $this->parser;
    }

    /**
     * Получает и возвращает список свойств товара
     * @param array $data
     * @return array
     */
    public function getPropertiesData(array $data): array
    {
        $parser = $this->getParser();

        $data['properties_link'] = $this->getCrawler()->filter('.div-match-form [jsource]')->count()
            ? $this->getCrawler()->filter('.div-match-form [jsource]')->attr('jsource')
            : null
        ;

        $properties_link = null;
        foreach ($data['item-bookmarks'] ?? [] as $item) {
            if (str_contains($item['title'], 'Характеристики')) {
                $properties_link = $item['link'];
                break;
            }
        }

        if (!$properties_link) {
            $data['properties'] = $parser->getProductPropertiesByHTML($this->getCrawler());
        }

        if (!$data['properties'] && !empty($properties_link)) {
            $data['properties'] = [
                'type' => 'array',
                'uri' => $properties_link,
                'items' => $parser->getProductProperties($properties_link),
            ];
        } elseif (!$data['properties'] && !empty($data['properties_link'])) {
            $data['properties'] = [
                'type' => 'link',
                'uri' => $data['properties_link'],
            ];
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        $parser = $this->getParser();

        $data = [
            'head' => $parser->getHeadData($this->getCrawler()),
            'breadcrumbs' => $parser->getBreadcrumbs($this->getCrawler()),
            'metaitemprop' => $parser->getProductMetaItemprop($this->getCrawler()),
            'title' => $this->getProductTitle()->getData(),
            'prices' => $parser->getProductPrices($this->getCrawler()),
            'gallery' => $parser->getProductGallery($this->getCrawler()),
            'name' => $this->getProductName()->getData(),
            'variants-color' => $parser->getProductVariantsColor($this->getCrawler()),
            'variants' => $parser->getProductVariants($this->getCrawler()),
            'tags' => $parser->getProductTaqs($this->getCrawler()),
            'features' => $parser->getProductFeatures($this->getCrawler()),
            'description' => $this->getTextBySelector($this->selectorProductDescription),
            'one-article' => $parser->getProductOneArticle($this->getCrawler()),
            'description-exp' => $parser->getProductDescriptionExp($this->getCrawler()),
            'models-param' => $parser->getProductModelsParam($this->getCrawler()),
            'item-bookmarks' => $parser->getProductItemBookmarks($this->getCrawler()),
            'properties' => [],
            'properties_link' => null,
        ];

        return $this->getPropertiesData($data);
    }
}
