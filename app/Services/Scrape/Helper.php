<?php
declare(strict_types=1);

namespace App\Services\Scrape;

final class Helper
{
    /**
     * Рекурсивно обрабатывает массив данных категории, извлекая из него ключи title и href,
     * создавая из них плоский массив ссылок
     * @param array $data Данные каталога
     * @return array Массив массивов
     */
    public function getPlainLinksFromCategory(array $data): array
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
                $result = array_merge($result, $this->getPlainLinksFromCategory($item));
            }
        }

        return $result;
    }

    /**
     * Возвращает идентификатор категории после извлечения из переданной ссылки
     * @param string $link
     * @return int|null Идентификатор или NULL
     */
    public function getCategoryIdFromLink(string $link): ?int
    {
        if (preg_match('~(/k(?<k>\d+)\.htm|/list/(?<l>\d+)/)~i', $link, $match)) {
            return (int) (($match['k'] ?? 0) ?: ($match['l'] ?? 0));
        }

        return null;
    }

    /**
     * Возвращает путь до файла сохранения категории по его идентификатору
     * @param int $id
     * @return string
     */
    public function getCategoryPathByCategoryId(int $id): string
    {
        return 'categories/' . $id . '.json';
    }
}
