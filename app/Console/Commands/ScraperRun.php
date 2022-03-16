<?php

namespace App\Console\Commands;

use App\Services\Scrape\EKatalogParser;
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

        foreach ($data['catalog'] as $catalog) {
            if (isset($catalog['sublist'])) {
                $catalog = array_merge([$catalog], $catalog['sublist']);
                unset($catalog[0]['sublist']);
            }

            foreach ($catalog as $item) {
                $exploded = explode('/', rtrim($item['href'], '/'));
                $categoryId = end($exploded);
                if (!is_numeric($categoryId)) {
                    dd('category_id_broken', $item);
                }
                $categoryPath = 'categories/' . $categoryId . '.json';

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
        }

        $this->output->writeln(' - categories (done)');

        return 0;
    }
}
