<?php

namespace App\Console\Commands;

use App\Services\Scrape\EKatalogParser;
use App\Services\Scrape\Entity\Category;
use App\Services\Scrape\Helper;
use App\Services\Scrape\ParserFactory;
use App\Services\Scrape\Persist;
use App\Services\Scrape\ValueObject\Product;
use Exception;
use Illuminate\Console\Command;
use Webmozart\Assert\Assert;

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

        $categoryId = 169;
        $category = Persist::load((new Category($categoryId, 'Fake'))->getCategoryPath());
        $categories = Helper::getPlainLinksFromCategory($category);
        $categories = array_map([Category::class, 'createByHref'], $categories);
        $categoryParser = ParserFactory::getByCategoryId($categoryId);
        Assert::notNull($categoryParser);

        /** @var Category[] $categories */
        foreach ($categories as $item) {
            $model = Helper::getCategoryModelData($item, $parser, 'wiz_char.php?id=' . $item->getId());
            if (!$model) {
                dd('broken_model', $item);
            } elseif (!isset($model['models']) || !$model['models']) {
                continue;
            }

            foreach ($model['models'] as $modelData) {
                $item->setVendor($modelData['brand']);

                $this->output->writeln(sprintf(
                    ' --- category "%s" (id: %s), vendor: %s',
                    $item->getTitle(), $item->getId(), $item->getVendor(),
                ));

                /** @var Product[] $products */
                $products = array_map([Product::class, 'create'], $modelData['models']);
                foreach ($products as $product) {
                    Helper::getProductData($item, $product, $categoryParser);
                }
            }
        }

        $this->output->writeln('done');

        return 0;
    }
}
