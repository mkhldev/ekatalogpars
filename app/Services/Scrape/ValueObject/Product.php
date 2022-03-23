<?php
declare(strict_types=1);

namespace App\Services\Scrape\ValueObject;

use App\Services\Scrape\Entity\Category;
use Webmozart\Assert\Assert;

class Product
{
    /**
     * Конструктор класса
     * @param string $title Название товара
     * @param string $link Ссылка на товар
     */
    public function __construct(
        private string $title,
        private string $link,
    )
    {
    }

    /**
     * Создание класса из переданного массива
     * @param array $data
     * @return static
     */
    public static function create(array $data): static
    {
        Assert::keyExists($data, 'title');
        Assert::keyExists($data, 'href');
        Assert::stringNotEmpty($data['title']);
        Assert::stringNotEmpty($data['href']);

        return new static($data['title'], $data['href']);
    }

    /**
     * Возвращать путь к JSON файлу товара
     * @param Category $category
     * @return string
     */
    public function getProductPath(Category $category): string
    {
        Assert::stringNotEmpty($category->getVendor());

        $baseName = pathinfo($this->link, PATHINFO_FILENAME);

        return strtolower('products/' . $category->getId() . '/' . $category->getVendor() . '/' . $baseName . '.json');
    }

    /**
     * Возвращать путь к HTML файлу товара
     * @param Category $category
     * @return string
     */
    public function getHTMLProductPath(Category $category): string
    {
        Assert::stringNotEmpty($category->getVendor());

        $baseName = pathinfo($this->link, PATHINFO_FILENAME);

        return strtolower('products-html/' . $category->getId() . '/' . $category->getVendor() . '/' . $baseName . '.htm');
    }
}
