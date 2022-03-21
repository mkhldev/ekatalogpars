<?php
declare(strict_types=1);

namespace App\Services\Scrape\Entity;

use App\Services\Scrape\Helper;
use Webmozart\Assert\Assert;

class Category
{
    /**
     * Конструктор класса
     * @param int $id Идентификатор
     * @param string $title Название категории
     * @param string|null $link Ссылка на категорию
     * @param string|null $vendor Название производителя
     */
    public function __construct(
        private int         $id,
        private string      $title,
        private string|null $link = null,
        private string|null $vendor = null,
    )
    {
    }

    /**
     * Создание класса из переданного массива
     * @param array $data
     * @return static
     */
    public static function createByHref(array $data): static
    {
        Assert::keyExists($data, 'href');
        Assert::keyExists($data, 'title');
        Assert::stringNotEmpty($data['href']);
        Assert::stringNotEmpty($data['title']);

        $categoryId = Helper::getCategoryIdFromLink($data['href']);
        Assert::positiveInteger($categoryId);

        return new static($categoryId, $data['title'], $data['href']);
    }

    /**
     * Возвращает идентификатор
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Возвращает название категории
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Возвращает ссылку на категорию или NULL
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * Устанавливает ссылку на категорию
     * @param string|null $link
     * @return Category
     */
    public function setLink(?string $link): Category
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Возвращает название производителя или NULL
     * @return string|null
     */
    public function getVendor(): ?string
    {
        return $this->vendor;
    }

    /**
     * Устанавливает название производителя
     * @param string|null $vendor
     * @return Category
     */
    public function setVendor(?string $vendor): Category
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * Возвращает путь до файла сохранения категории по его идентификатору
     * @return string
     */
    public function getCategoryPath(): string
    {
        return 'categories/' . $this->id . '.json';
    }

    /**
     * Возвращает путь до файла сохранения списка моделей категории по идентификатору категории
     * @return string
     */
    public function getModelsPath(): string
    {
        return 'models/' . $this->id . '.json';
    }
}
