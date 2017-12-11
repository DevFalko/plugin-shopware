<?php
/**
 * Created by PhpStorm.
 * User: sl
 * Date: 05.10.17
 * Time: 12:20
 */

namespace VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Repositories\AiPhilos;


use Aiphilos\Api\Items\ClientInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Locale;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Helpers\LocaleStringMapperInterface;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Repositories\Shopware\ArticleRepositoryInterface as SwArticleRepository;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Schemes\Mappers\SchemeMapperInterface;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Schemes\ArticleSchemeInterface;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Traits\ApiUserTrait;

class BasicArticleRepository implements ArticleRepositoryInterface
{
    use ApiUserTrait;

    /** @var LocaleStringMapperInterface */
    private $localeMapper;

    /** @var string */
    private $language;

    /** @var SwArticleRepository */
    private $swArticleRepository;

    /** @var ArticleSchemeInterface */
    private $scheme;

    private $priceGroup = 'EK';

    private $localeId = 0;

    private $originalLocale = '';
    /** @var ModelManager */
    private $modelManager;
    /** @var SchemeMapperInterface */
    private $schemeMapper;
    /** @var int */
    private $salesMonths = 3;

    /**
     * DatabaseCrud constructor.
     * @param LocaleStringMapperInterface $localeMapper
     * @param ClientInterface $itemClient
     * @param ArticleSchemeInterface $scheme
     * @param ModelManager $modelManager
     * @param SchemeMapperInterface $schemeMapper
     * @param \Zend_Cache_Core $cache
     */
    public function __construct(
        LocaleStringMapperInterface $localeMapper,
        ClientInterface $itemClient,
        ArticleSchemeInterface $scheme,
        ModelManager $modelManager,
        SchemeMapperInterface $schemeMapper,
        \Zend_Cache_Core $cache
    ) {
        $this->localeMapper = $localeMapper;
        $this->itemClient = $itemClient;
        $this->scheme = $scheme;
        $this->swArticleRepository = $scheme->getRepository();
        $this->modelManager = $modelManager;
        $this->schemeMapper = $schemeMapper;
        $this->cache = $cache;
    }

    /**
     * @param array $pluginConfig
     * @return $this
     */
    public function setPluginConfig(array $pluginConfig) {
        $this->pluginConfig = $pluginConfig;
        $this->updateConfigRelatedOps();

        return $this;
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale) {
        $this->originalLocale = $locale;
        $localeModel = $this->modelManager->getRepository(Locale::class)->findOneBy(['locale' => $locale]);
        if (!$localeModel) {
            throw new \InvalidArgumentException('No matching locale model for locale "' . $locale . '"');
        }
        $this->localeId = $localeModel->getId();
        $this->language = $this->localeMapper->mapLocaleString($locale);
        $this->updateConfigRelatedOps();
        $this->salesMonths = $this->pluginConfig['salesMonths'];

        return $this;
    }

    /**
     * @param string $priceGroup
     * @return BasicArticleRepository
     */
    public function setPriceGroup($priceGroup) {
        $this->priceGroup = $priceGroup;
        return $this;
    }


    private function updateConfigRelatedOps() {
        if ($this->pluginConfig) {
            $this->salesMonths = $this->pluginConfig['salesMonths'];
            if ($this->language) {
                $this->setAuthentication();
                $langValid = $this->validateLanguage($this->language);

                if (!$langValid) {
                    throw new \InvalidArgumentException('Language "' . $this->language . '" is not valid.');
                }

                $this->itemClient->setDefaultLanguage($this->language);

                $this->setDbName();
            }
        }
    }

    public function createArticle($articleId) {
        return $this->createArticles([$articleId]);
    }

    public function getArticle($articleId) {
        return $this->itemClient->getItem($articleId);
    }

    public function updateArticle($articleId) {
        return $this->updateArticles([$articleId]);
    }

    public function deleteArticle($articleId) {
        return $this->itemClient->deleteItem($articleId);
    }

    public function createArticles(array $articleIds) {
        $articles = $this->swArticleRepository->getArticleData($articleIds, [], $this->localeId, $this->priceGroup, $this->salesMonths);

        $mappedArticles = $this->schemeMapper->map($this->scheme, $articles);

        foreach ($mappedArticles as &$mappedArticle) {
            $mappedArticle['_action'] = 'POST';
        }

        return $this->itemClient->batchItems($mappedArticles);
    }

    public function getArticles() {
        return $this->itemClient->getItems(['size' => 10000]);
    }

    public function updateArticles(array $articleIds) {
        $articles = $this->swArticleRepository->getArticleData($articleIds, [], $this->localeId, $this->priceGroup, $this->salesMonths);

        $mappedArticles = $this->schemeMapper->map($this->scheme, $articles);

        foreach ($mappedArticles as &$mappedArticle) {
            $mappedArticle['_action'] = 'PUT';
        }

        return $this->itemClient->batchItems($mappedArticles);
    }

    public function deleteArticles(array $articleIds) {
        $data = [];
        foreach ($articleIds as $articleId) {
            $data[] = ['_id' => $articleId, '_action' => 'DELETE'];
        }
        return $this->itemClient->batchItems($data);
    }

}