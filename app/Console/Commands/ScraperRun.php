<?php

namespace App\Console\Commands;

use App\Services\Scrape\EKatalogParser;
use App\Services\Scrape\Helper;
use App\Services\Scrape\Persist;
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
        $helper = new Helper();

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
            throw new \Exception('Unknown key: catalog');
        }
        $this->output->writeln(' - home (done)');

        // =================================================================
        // categories from home
        // =================================================================

        $catalog = $helper->getPlainLinksFromCategory($data['catalog']);
        foreach ($catalog as $item) {
            if (!$categoryId = $helper->getCategoryIdFromLink($item['href'])) {
                dd('broken_category', $item);
            }
            $categoryPath = $helper->getCategoryPathByCategoryId($categoryId);

            $isSaved = true;
            if (!$cData = Persist::load($categoryPath)) {
                $this->output->writeln(' - ' . $categoryPath);
                $cData = $parser->category($item['href']);
                $isSaved = false;
            }

            if (!$isSaved) {
                Persist::save($categoryPath, $cData);
            }
        }

        $this->output->writeln(' - categories (done)');

        return 0;
    }
}
