<?php
declare(strict_types=1);

namespace App\Services\Scrape;

use App\Services\Scrape\Entity\Category;
use App\Services\Scrape\Parser\AbstractParser;
use App\Services\Scrape\ValueObject\Product;
use Webmozart\Assert\Assert;

final class Helper
{
    /**
     * Рекурсивно обрабатывает массив данных категории, извлекая из него ключи title и href,
     * создавая из них плоский массив ссылок
     * @param array $data Данные каталога
     * @return array Массив массивов
     */
    public static function getPlainLinksFromCategory(array $data): array
    {
        $result = [];

        if (isset($data['title'], $data['href']) && !empty($data['title']) && !empty($data['href'])) {
            $result[] = [
                'title' => trim((string) $data['title']),
                'href' => trim((string) $data['href']),
            ];
        }

        foreach ($data as $item) {
            if (is_array($item)) {
                $result = array_merge($result, self::getPlainLinksFromCategory($item));
            }
        }

        return $result;
    }

    /**
     * Возвращает идентификатор категории после извлечения из переданной ссылки
     * @param string $link
     * @return int|null Идентификатор или NULL
     */
    public static function getCategoryIdFromLink(string $link): ?int
    {
        if (preg_match('~(/k(?<k>\d+)\.htm|/list/(?<l>\d+)/)~i', $link, $match)) {
            return (int) (($match['k'] ?? 0) ?: ($match['l'] ?? 0));
        }

        return null;
    }

    /**
     * @param Category $category
     * @param EKatalogParser|null $parser
     * @return array
     */
    public static function getCategoryData(
        Category $category,
        ?EKatalogParser $parser = null,
    ): array
    {
        if (!$data = Persist::load($category->getCategoryPath())) {
            Assert::stringNotEmpty($category->getLink());
            Assert::notNull($parser);

            $data = $parser->category($category->getLink());
            Persist::save($category->getCategoryPath(), $data);
        }

        return $data;
    }

    /**
     * @param Category $category
     * @param EKatalogParser|null $parser
     * @param string|null $downloadUrl
     * @return array
     */
    public static function getCategoryModelData(
        Category $category,
        ?EKatalogParser $parser = null,
        ?string $downloadUrl = null,
    ): array
    {
        if (!$data = Persist::load($category->getModelsPath())) {
            Assert::stringNotEmpty($category->getLink());
            Assert::notNull($parser);
            Assert::stringNotEmpty($downloadUrl);

            $data = $parser->getDescriptionsProductLinksList($downloadUrl);
            Persist::save($category->getModelsPath(), $data);
        }

        return $data;
    }

    /**
     * @param Category $category
     * @param Product $product
     * @param AbstractParser|null $parser
     * @return array
     */
    public static function getProductData(
        Category $category,
        Product $product,
        ?AbstractParser $parser = null,
    ): array
    {
        return [];
    }
}
