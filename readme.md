## Scraper

### Требуемое ПО

- [PHP 8.1](https://www.php.net/downloads)
- [FileInfo](https://www.php.net/manual/ru/fileinfo.installation.php)

### Установка зависимостей composer

```shell
composer install --ignore-platform-reqs
```

### Запуск парсера из консоли

```shell
php artisan scraper:run
```

После окончания работы парсера в папке `storage/app/pars/` появятся файлы.

### Подсказки

Основной pipeline парсера в файле `\App\Console\Commands\ScraperRun`.

Методы парсера разбивающие страницу на элементы в файле `\App\Services\Scrape\EKatalogParser`.

Дополнительные хелперы преобразующие данные в файле `\App\Services\Scrape\Helper`.

### Структура

Sitemap: https://kz.e-katalog.com/sitemap/sitemap-index.xml

```text
Товары в категории: https://kz.e-katalog.com/wiz_char.php?id=298, где
298 - идентификатор категории
```

```text
Формат пути до товара: /vendor-product-name.htm, где
vendor - slug поставщика
product-name - slug наименования товара

Пример: https://kz.e-katalog.com/ACER-TRAVELMATE-P2-TMP214-52G.htm
```
