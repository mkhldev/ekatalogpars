### Scraper

Добавить модули в php.ini:
- [FileInfo](https://www.php.net/manual/ru/fileinfo.installation.php)

Запуск парсера из консоли:
```shell
php artisan scraper:run
```

После окончания работы парсера в папке `storage/app/pars/` появятся файлы.

Основной pipeline парсера в файле `\App\Console\Commands\ScraperRun`.
Методы парсера разбивающие страницу на элементы в файле `\App\Services\Scrape\EKatalogParser`.
