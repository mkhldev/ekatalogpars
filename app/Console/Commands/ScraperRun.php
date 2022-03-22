<?php

namespace App\Console\Commands;

use App\Services\Scrape\EKatalogParser;
use App\Services\Scrape\Entity\Category;
use App\Services\Scrape\Helper;
use App\Services\Scrape\Persist;
use App\Services\Scrape\ValueObject\Good;
use Exception;
use Illuminate\Console\Command;

class ScraperRun extends Command
{
    /**
     * @inheritDoc
     */
    protected $signature = 'scraper:run';

    /**
     * @inheritDoc
     */
    protected $description = 'Scraper run!';

    public function handle(): int
    {
        $this->output->writeln($this->description);

        $parser = new EKatalogParser();

        // =================================================================
        // home
        // =================================================================

        $path = 'home.json';
        if (!$data = Persist::load($path)) {
            $this->output->writeln(' - ' . $path);
            $data = $parser->home();
        }
        Persist::save('home.json', $data);

        if (!isset($data['catalog']) || !is_array($data['catalog'])) {
            throw new Exception('Unknown key: catalog');
        }
        $this->output->writeln(' - home (done)');

        // =================================================================
        // categories from home
        // =================================================================

        $categories = Helper::getPlainLinksFromCategory($data['catalog']);
        $categories = array_map([Category::class, 'createByHref'], $categories);

        /** @var Category[] $categories */
        foreach ($categories as $item) {
            if (!Persist::isSaved($item->getCategoryPath())) {
                Helper::getCategoryData($item, $parser);
            }
        }

        $this->output->writeln(' - categories (done)');

        // =================================================================
        // all models from categories pages
        // =================================================================

        foreach ($categories as $item) {
            if (!Persist::isSaved($item->getModelsPath())) {
                Helper::getCategoryModelData($item, $parser, 'wiz_char.php?id=' . $item->getId());
            }
        }

        $this->output->writeln(' - models on categories (done)');

        // =================================================================
        // category: computers
        // =================================================================

        $category = Persist::load((new Category(169, 'Fake'))->getCategoryPath());
        $categories = Helper::getPlainLinksFromCategory($category);
        $categories = array_map([Category::class, 'createByHref'], $categories);

        /** @var Category[] $categories */
        foreach ($categories as $item) {
            $model = Helper::getCategoryModelData($item, $parser, 'wiz_char.php?id=' . $item->getId());
            if (!$model) {
                dd('broken_model', $item);
            }

            foreach ($model['models'] as $modelData) {
                $item->setVendor($modelData['brand']);

                /** @var Good[] $goods */
                $goods = array_map([Good::class, 'create'], $modelData['models']);
                foreach ($goods as $good) {
                    $data = Helper::getGoodData($item, $good, $parser);

                    dd($data, 'todo');
                }
            }
        }

        return 0;
    }
}
