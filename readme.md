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
