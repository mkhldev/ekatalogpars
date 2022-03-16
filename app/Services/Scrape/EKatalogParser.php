<?php

namespace App\Services\Scrape;

use Symfony\Component\DomCrawler\Crawler;

class EKatalogParser
{
    private string $url = 'https://kz.e-katalog.com/';
    private GoutteClient $client;

    public function __construct()
    {
        $this->client = new GoutteClient();
    }

    public function getHeadData(Crawler $crawler): array
    {
        $headParser = new HeadParser($crawler);

        return [
            'title' => $headParser->getTitle(),
            'meta' => $headParser->getMeta()
        ];
    }

    public function getBreadcrumbs(Crawler $crawler): array
    {
        $path = $crawler->filter('.catalog-path')->first();

        return $path->filter('a')->each(function (Crawler $a) {
            return [
                'title' => $a->text(),
                'href' => $a->link()->getUri(),
                'link' => $a->attr('link'),
            ];
        });
    }

    public function getCategoryTitle(Crawler $crawler): string
    {
        return $crawler->filter('.page-title')->text();
    }

    public function getCategoryContent(Crawler $crawler): array
    {
        $title = $crawler->filter('.gl-top-title');
        if ($title->count() === 0) {
            return [];
        }
        $content = $crawler->filter('.gl-cont-div');
        $items = $crawler->filter('.gl-main-div')->children()->each(function (Crawler $a) {
            return $a;
        });

        $glossary = [];
        $id = null;
        foreach ($items as $item) {
            // dd($item->tagName());
            if ($item->nodeName() === 'a') {
                $id = str_replace('p', '', $item->attr('name'));
                $glossary[$id]['id'] = $id;
            } elseif ($item->nodeName() === 'h3') {
                $glossary[$id]['title'] = $item->text();
            } else {
                $glossary[$id]['text'] = $item->html();
            }
        }

        return [
            'title' => $title->count() ? $title->text() : '',
            'content' => $content->filter('a')->each(function (Crawler $a) {
                return [
                    'title' => $a->text(),
                    'id' => str_replace('#p', '', $a->attr('href')),
                ];
            }),
            'glossary' => $glossary,
        ];
    }

    public function getSubCategories(Crawler $crawler): array
    {
        return $crawler->filter('.subcats-td')->each(function (Crawler $td) {
            return $td->children()->each(function (Crawler $children) {
                if ($children->nodeName() === 'a') {
                    return [
                        'title' => $children->text(),
                        'href' => $children->link()->getUri(),
                        'class' => $children->attr('class')
                    ];
                }

                if ($children->nodeName() === 'sub') {
                    return false;
                }

                if ($children->nodeName() === 'div') {
                    return [
                        'class' => $children->attr('class'),
                        'children' => $children->filter('a')->each(function (Crawler $a) {
                            return [
                                'title' => $a->text(),
                                'href' => $a->link()->getUri()
                            ];
                        })
                    ];
                }
                return $children->nodeName();
            });
        });
    }

    public function home(): array
    {
        $crawler = $this->client->get($this->url);
        $data = [];
        $data['head'] = $this->getHeadData($crawler);

        $data['mainmenu'] = $crawler->filter('.mainmenu-item')->each(function (Crawler $node) {
            $cat = [];

            $cat['sublist'] = $node->filter('.mainmenu-sublist a')->each(function (Crawler $a) {
                return [
                    'title' => $a->text(),
                    'href' => $a->link()->getUri(),
                    'img' => $a->filter('img')->image()->getUri(),
                    'class' => $a->attr('class')
                ];
            });

            $tl = $node->filter('.mainmenu-link');
            if ($tl->nodeName() === 'a') {
                $cat['title'] = $tl->text();
                $cat['href'] = $tl->link()->getUri();
            } else {
                $cat['title'] = $tl->text();
            }

            return $cat;
        });

        $data['catalog'] = $crawler->filter('.s-catalog-subcat')->each(function (Crawler $node) {
            $cat = [];

            $tl = $node->filter('.s-catalog-cat');
            if ($tl->nodeName() === 'a') {
                $cat['title'] = $tl->text();
                $cat['href'] = $tl->link()->getUri();
            } else {
                $cat['title'] = $tl->text();
            }

            $cat['sublist'] = $node->filter('span a')->each(function (Crawler $a) {
                return [
                    'title' => $a->text(),
                    'href' => $a->link()->getUri(),
                ];
            });

            return $cat;
        });

        return $data;
    }

    public function category(string $uri): array
    {
        $crawler = $this->client->get($uri);
        $data = [];
        $data['head'] = $this->getHeadData($crawler);
        $data['breadcrumbs'] = $this->getBreadcrumbs($crawler);
        $data['title'] = $this->getCategoryTitle($crawler);
        $data['subcats'] = $this->getSubCategories($crawler);
        $data['description'] = $this->getCategoryContent($crawler);
        $data['presets'] = $this->getPresets($crawler);
        $data['bottom-info'] = $this->getBottomInfo($crawler);

        return $data;
    }

    public function getBottomInfo(Crawler $crawler): string
    {
        $node = $crawler->filter('.bottom-links-oes')->getNode(0);
        $node->parentNode->removeChild($node);
        $node = $crawler->filter('.bottom-btn-links')->getNode(0);
        $node->parentNode->removeChild($node);
        $node = $crawler->filter('.bottom-tec-text')->getNode(0);
        $node->parentNode->removeChild($node);

        return $crawler->filter('.bottom-info')->html();
    }

    public function getPresets(Crawler $crawler): array
    {
        $form = $crawler->filter('#form_match');
        if (!$form->count()) {
            return [];
        }

        return $form->filter('div[id^="preset_t_"]')->each(function (Crawler $preset) use ($crawler) {
            $span = $preset->filter('span');
            return [
                'title' => $preset->text(),
                'id' => str_replace('preset_t_', '', $preset->attr('id')),
                'items' => $this->getPresetList($crawler->filter('ul#' . $preset->attr('jclose'))),
                'helping' => $span->count() ? $span->attr('jsource') : ''
            ];
        });
    }

    public function getPresetList(Crawler $crawler): array
    {
        $presets = [];
        if (!$crawler->count()) {
            return [];
        }

        if (str_contains($crawler->attr('class'), 'color-plate')) {
            $presets['type'] = 'colors';
            $presets['items'] = $crawler->filter('li label')->each(function (Crawler $preset) {
                $span = $preset->filter('span');
                return [
                    'id' => str_replace('cl', '', $preset->attr('for')),
                    'title' => $span->attr('title'),
                    'value' => $this->getColorBackground($span->attr('style'))
                ];
            });
        } else {
            $presets['type'] = 'pr';
            $presets['items'] = $crawler->filter('li')->each(function (Crawler $preset) {
                $label = $preset->filter('label');
                $span = $preset->filter('span');
                return [
                    'id' => str_replace('c', '', $label->attr('for')),
                    'title' => $label->text(),
                    'helping' => $span->count() ? $span->attr('jsource') : ''
                ];
            });
        }

        return $presets;
    }

    public function getColorBackground(string $str): array
    {
        if (str_contains($str, 'background-image')) {
            preg_match_all('/background-image: url\((.*?)\)/m', $str, $matches, PREG_SET_ORDER, 0);
            return [
                'type' => 'img',
                'val' => $matches[0][1] ?? ''
            ];
        }

        $matches = explode('#', $str);
        return [
            'type' => 'color',
            'val' => $matches[1] ?? ''
        ];
    }

    /**
     * Каталог брендов
     * @param string|null $uri
     * @return array
     */
    public function brandPagesList(string|null $uri = null): array
    {
        if ($uri === null) {
            $uri = $this->url . 'katalog/';
        }

        $crawler = $this->client->get($uri);
        return $crawler->filter('.list-pager a')->each(function (Crawler $link) {
            return $link->link()->getUri();
        });
    }

    public function getBrand($uri): array
    {
        $crawler = $this->client->get($uri);

        return $crawler
            ->filter('.txt-br-l a')->each(function (Crawler $a) {
                return [
                    'title' => $a->text(),
                    'href' => $a->link()->getUri(),
                ];
            });
    }

    /**
     * https://kz.e-katalog.com/mtools/mui_get_img_gallery.php?idg_=2175434&f_type_=IMG&callback=jQuery22402804003455135_1647072116153&_=1647072116154
     */
    public function getProductProperties($uri): array
    {
        $crawler = $this->client->get($uri);
        $help_table = $crawler->filter("#help_table td.op01 table > tr")->each(function (Crawler $node) {
            return $node;
        });

        $properties = [
            'Основное' => ['title' => 'Основное', 'items' => []]
        ];
        $cur = 'Основное';

        foreach ($help_table as $node) {
            if ($node->attr('class') === 'item-col-tr') {
                $properties['Цвет'] = [
                    'title' => 'Цвет',
                    'items' => $node->filter(".color-plate > div")->each(function (Crawler $color) {
                        return [
                            'color' => $this->getColorBackground($color->attr('style')),
                            'title' => $color->attr('title'),
                            'href' => $color->attr('jsource'),
                        ];
                    })
                ];
            } elseif ($node->attr('valign') === 'top') {
                $op = $node->filter(".op1 > span");
                if ($op->attr('class') === 'op1-title') {
                    $cur = $op->text();
                    $properties[$cur] = ['title' => $cur, 'items' => []];
                } else {
                    $properties[$cur]['items'][] = [
                        'name' => $op->text(),
                        'href' => $op->attr('jsource'),
                        'val' => $node->filter('.op3')->html()
                    ];
                }
            }
        }

        return $properties;
    }

    public function getProduct($uri): array
    {
        $crawler = $this->client->get($uri);
        $data = [];
        $data['head'] = $this->getHeadData($crawler);
        $data['breadcrumbs'] = $this->getBreadcrumbs($crawler);
        $data['metaitemprop'] = $this->getProductMetaItemprop($crawler);
        $data['title'] = $this->getProductTitle($crawler);
        $data['prices'] = $this->getProductPrices($crawler);
        $data['gallery'] = $this->getProductGallery($crawler);
        $data['name'] = $this->getProductName($crawler);
        $data['variants-color'] = $this->getProductVariantsColor($crawler);
        $data['variants'] = $this->getProductVariants($crawler);
        $data['tags'] = $this->getProductTaqs($crawler);
        $data['features'] = $this->getProductFeatures($crawler);
        $data['description'] = $this->getProductDescription($crawler);
        $data['one-article'] = $this->getProductOneArticle($crawler);
        $data['description-exp'] = $this->getProductDescriptionExp($crawler);
        $data['models-param'] = $this->getProductModelsParam($crawler);
        $data['item-bookmarks'] = $this->getProductItemBookmarks($crawler);
        $data['properties_link'] = $crawler->filter('.list-more-div-small')->count() ? $crawler->filter('.list-more-div-small')->attr('jsource') : '';

        $properties_link = '';
        foreach ($data['item-bookmarks'] ?? [] as $item) {
            if ($item['title'] == 'Характеристики') {
                $properties_link = $item['link'];
            }
        }
        if (!empty($properties_link)) {
            $data['properties'] = ['type' => 'array', 'uri' => $properties_link, 'items' => $this->getProductProperties('https://kz.e-katalog.com' . $properties_link)];
        } elseif (!empty($data['properties_link'])) {
            $data['properties'] = ['type' => 'link', 'uri' => $data['properties_link']];
        }

        return $data;
    }

    public function getProductItemBookmarks(Crawler $crawler): array
    {
        return $crawler->filter("#item-bookmarks > a")->each(function (Crawler $a) {
            return [
                'title' => $a->text(),
                'href' => $a->link()->getUri()/* attr('href') */,
                'link' => $a->attr('link'),
            ];
        });
    }

    public function getProductOneArticle(Crawler $crawler): array
    {
        $article = $crawler->filter(".one-article > a");
        if ($article->count() === 0) {
            return [];
        }

        return [
            'text' => $article->filter('.article__right-block')->text(),
            'href' => $crawler->attr('href'),
            'img' => $crawler->filter('img')->attr('src'),
        ];
    }

    public function getProductDescriptionExp(Crawler $crawler): string
    {
        return $crawler->filter("#eski-i-other-txt-2")->count() ? $crawler->filter("#eski-i-other-txt-2")->html() : '';
    }

    public function getProductDescription(Crawler $crawler): string
    {
        return $crawler->filter("#eski-i-other-txt-1")->html();
    }

    public function getProductPrices(Crawler $crawler): array
    {
        $prices = $crawler->filter(".desc-short-prices > a");
        if ($prices->count() === 0) {
            return [];
        }

        return [
            'text' => $prices->text(),
            'href' => $prices->attr('link'),
        ];
    }

    public function getProductModelsParam(Crawler $crawler): array
    {
        return $crawler->filter('.models-param')->each(function (Crawler $node) {
            $models_list = $node->filter('.models-list');
            return [
                'title' => $node->filter('.h2')->text(),
                'type' => $models_list->attr('id'),
                'list' => $this->getProductModelsParamList($models_list, $models_list->attr('id')),
            ];
        });
    }

    public function getProductModelsParamList(Crawler $crawler, $id): array
    {
        if ($id === 'j_models_menu2-brands') {
            return $crawler->filter('a')->each(function (Crawler $node) {
                return [
                    'id' => $node->attr('id'),
                    'text' => $node->text(),
                    'href' => $node->attr('href'),
                ];
            });
        }
        if ($id === 'slice_setup') {
            return $crawler->filter('em')->each(function (Crawler $node) {
                return [
                    'text' => $node->text(),
                    'href' => $node->attr('jsource'),
                ];
            });
        }
        return $crawler->filter('a')->each(function (Crawler $node) {
            return [
                'id' => $node->attr('id'),
                'text' => $node->text(),
                'href' => $node->attr('href'),
            ];
        });
    }

    public function getProductMetaItemprop(Crawler $crawler): array
    {
        return $crawler->filter('[itemprop]')->each(function (Crawler $meta) {
            return [
                'itemprop' => $meta->attr('itemprop'),
                'content' => $meta->attr('content'),
                'text' => $meta->text(),
            ];
        });
    }

    public function getProductTitle(Crawler $crawler): array
    {
        return [
            'text' => $crawler->filter("#top-page-title h1")->text(),
            'option' => $crawler->filter("#top-page-title h1 span")->text(),
        ];
    }

    public function getProductGallery(Crawler $crawler): array
    {
        return [
            'photo' => $crawler->filter("#mobi-num-photo")->count() ? $crawler->filter("#mobi-num-photo")->attr('data-url') : '',
            'video' => $crawler->filter("#mobi-num-video")->count() ? $crawler->filter("#mobi-num-video")->attr('data-url') : '',
        ];
    }

    public function getProductName(Crawler $crawler): array
    {
        return [
            'text' => $crawler->filter("div.cont-block-title.no-mobile > span")->text(),
            'option' => $crawler->filter("div.cont-block-title.no-mobile > span > span")->text(),
        ];
    }

    public function getProductVariantsColor(Crawler $crawler): array
    {
        return $crawler->filter(".item-color-plate.no-mobile > div")->each(function (Crawler $color) {
            return [
                'color' => $this->getColorBackground($color->attr('style')),
                'title' => $color->attr('title'),
                'href' => $color->attr('jsource'),
            ];
        });
    }

    public function getProductVariants(Crawler $crawler): array
    {
        return $crawler->filter("div.m-c-f1-pl--inline > span")->each(function (Crawler $node) {
            $current = str_contains($node->attr('class'), 'current');

            return [
                'text' => $node->text(),
                'title' => $node->attr('title'),
                'href' => $current ? '' : $node->filter('a')->link()->getUri()/* $node->filter('a')->attr('href') */,
                'current' => $current
            ];
        });
    }

    public function getProductTaqs(Crawler $crawler): array
    {
        return $crawler->filter("div.m-c-f1 > a")->each(function (Crawler $node) {
            return [
                'text' => $node->text(),
                'title' => $node->attr('title'),
                'href' => $node->filter('a')->attr('href'),
                'jcontent' => $node->attr('jcontent')
            ];
        });
    }

    public function getProductFeatures(Crawler $crawler): array
    {
        return $crawler->filter("div.m-c-f2 > div")->each(function (Crawler $node) {
            $f = explode(':', $node->text());
            return [
                'title' => trim($f[0] ?? ''),
                'val' => trim($f[1] ?? '')
            ];
        });
    }

    /**
     * Descriptions
     * @param [type] $uri
     * @return array
     */
    public function getDescriptionsProductLinksList($uri): array
    {
        $crawler = $this->client->get($uri);
        $data = [];
        $data['head'] = $this->getHeadData($crawler);
        $data['breadcrumbs'] = $this->getBreadcrumbs($crawler);
        $data['presets'] = $this->getPresets($crawler);

        $divs = $crawler->filter(".main-part-content > div")->each(function (Crawler $node) {
            return $node;
        });

        $data['models'] = [];
        $cur = 0;

        foreach ($divs as $node) {
            if ($node->attr('class') === 'map-brand') {
                $cur = $node->text();
                $data['models'][$cur] = ['brand' => $cur, 'models' => []];
            } elseif ($node->attr('class') === 'map-models') {
                $data['models'][$cur]['models'] = $node->filter('a')->each(function (Crawler $a) {
                    return [
                        'title' => $a->text(),
                        'href' => $a->link()->getUri(),
                    ];
                });
            }
        }

        $data['models'] = array_values($data['models']);

        return $data;
    }

    /**
     * Undocumented function
     * @param [type] $uri
     * @return array
     */
    public function getPost($uri): array
    {
        $crawler = $this->client->get($uri);
        $data = [];
        $data['head'] = $this->getHeadData($crawler);
        $data['breadcrumbs'] = $this->getBreadcrumbs($crawler);
        $data['content'] = $this->postContent($crawler);
        $data['presets'] = $this->getPresets($crawler);
        $data['id'] = $crawler->filter('a[name^="post-dis-"]')->count() ? $crawler->filter('a[name^="post-dis-"]')->attr('name') : time();

        return $data;
    }

    public function postContent(Crawler $crawler): array
    {
        return [
            'title' => $crawler->filter('.post-title h1')->text(),
            'date' => $crawler->filter('.post-title .inside-post-date')->text(),
            'main-pic' => $crawler->filter('.post-main-pic > img')->attr('src'),
            'author' => $crawler->filter('.post-author-new')->text(),
            'text' => $crawler->filter('.post-content')->html(),
        ];
    }

    public function getPostPagesList(): array
    {
        $data = [];
        for ($i = 0; $i < 33; $i++) {
            $uri = $this->url . "/ek-post.php?katalog_=1&view_=posts&page_={$i}&mode_=blog";
            $crawler = $this->client->get($uri);
            $data = array_merge(
                $data,
                $crawler->filter('a.post-title-link')->each(function (Crawler $a) {
                    return $a->link()->getUri();
                })
            );

            echo PHP_EOL . $i . ' ' . count($data);
        }

        return $data;
    }
}
