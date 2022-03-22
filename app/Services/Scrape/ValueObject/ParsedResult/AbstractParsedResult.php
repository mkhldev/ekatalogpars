<?php

namespace App\Services\Scrape\ValueObject\ParsedResult;

use Exception;
use Webmozart\Assert\Assert;

abstract class AbstractParsedResult
{
    /**
     * Структура
     * @var array
     */
    protected array $structure = [];

    /**
     * Данные
     * @param array $data
     */
    public function __construct(
        protected array $data
    )
    {
    }

    /**
     * Валидация структуры и данных
     * @return bool
     * @throws Exception
     */
    public function validate(): bool
    {
        try {
            Assert::notEmpty($this->structure, 'Не установлена структура');
            Assert::notEmpty($this->data, 'Пустые данные');

            foreach ($this->structure as $key => $type) {
                if (!array_key_exists($key, $this->data)) {
                    throw new Exception('Нет ключа: ' . $key);
                }
            }
        } catch (Exception $e) {
            throw new Exception(sprintf('[%s] %s', get_class($this), $e->getMessage()));
        }

        return true;
    }

    /**
     * Валидирует и возвращает данные
     * @return array
     * @throws Exception
     */
    public function getData(): array
    {
        $this->validate();

        return $this->data;
    }
}
