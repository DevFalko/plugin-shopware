<?php
/**
 * Created by PhpStorm.
 * User: sl
 * Date: 16.11.17
 * Time: 09:17
 */

namespace VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Cron;


use Doctrine\DBAL\Connection;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Article\Detail;
use Shopware\Models\Shop\Locale;
use Shopware\Models\Shop\Shop;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Helpers\LocaleStringMapperInterface;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Initializers\CreateResultEnum;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Initializers\DatabaseInitializerInterface;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Repositories\AiPhilos\ArticleRepositoryInterface;

class BasicDatabaseSynchronizer implements DatabaseSynchronizerInterface
{
    /** @var DatabaseInitializerInterface */
    private $databaseInitializer;

    /** @var ModelManager */
    private $modelManager;

    /** @var ConfigReader */
    private $configReader;

    /** @var LocaleStringMapperInterface */
    private $localeMapper;

    /** @var ArticleRepositoryInterface */
    private $aiRepository;

    /**
     * BasicDatabaseSynchronizer constructor.
     * @param DatabaseInitializerInterface $databaseInitializer
     * @param ModelManager $modelManager
     * @param ConfigReader $configReader
     * @param LocaleStringMapperInterface $localeMapper
     * @param ArticleRepositoryInterface $aiRepository
     */
    public function __construct(
        DatabaseInitializerInterface $databaseInitializer,
        ModelManager $modelManager,
        ConfigReader $configReader,
        LocaleStringMapperInterface $localeMapper,
        ArticleRepositoryInterface $aiRepository
    ) {
        $this->databaseInitializer = $databaseInitializer;
        $this->modelManager = $modelManager;
        $this->configReader = $configReader;
        $this->localeMapper = $localeMapper;
        $this->aiRepository = $aiRepository;
    }


    public function sync() {
        $shops = $this->getShops();

        $results = "Processing shops:" . PHP_EOL . PHP_EOL;
        foreach ($shops as $shop) {
            $results .= 'Now processing "' . $shop->getName() .'"' . PHP_EOL;
            $config = $this->getConfigForShop($shop);
            if (!$config) {
                $results .= 'Skipped: Could not fetch plugin configuration.' . PHP_EOL;
                continue;
            }

            if (!$config['useAiSearch']) {
                $results .= 'Skipped: Search disabled for this shop.' . PHP_EOL;
                continue;
            }

            /** @var Locale $locale */
            $locale = $shop->getLocale();
            $language = $this->mapLocale($locale);
            if (!$language) {
                $results .= 'Skipped: Could not map locale "' . $locale->getLocale() . '"' . PHP_EOL;
                continue;
            }

            try {
                $createResult = $this->databaseInitializer->createSchemeIfNotExist($language, $config);
            } catch (\Exception $e) {
                $results .= 'Error: An exception occurred; ' . PHP_EOL . $e->getMessage() . PHP_EOL . PHP_EOL;
                continue;
            }

            $err = false;
            $msg = "";
            switch ($createResult) {
                case CreateResultEnum::LANGUAGE_NOT_SUPPORTED:
                    $msg = 'Language "' . $language . '" not supported by API.';
                    $err = true;
                    break;
                case CreateResultEnum::ALREADY_EXISTS:
                    $msg = 'Database already exists.';
                    break;
                case CreateResultEnum::CREATED:
                    $msg = 'Database created.';
                    break;
                case CreateResultEnum::NAME_ERROR:
                    $msg = 'Failed to set database name';
                    $err = true;
                    break;
            }
            $results .= ($err ? 'Error: ' : 'Info: ') . $msg . PHP_EOL;
            if ($err) {
                continue;
            }

            $msg = "";
            try {
                $this->updateDB($shop, $config);
                $msg = 'Successfully updated database!';
            } catch (\Exception $e) {
                $msg = 'Error: Failed to updated database with exception;' . PHP_EOL . $e->getMessage();
            }

            $results .= $msg . PHP_EOL . PHP_EOL . 'Done' . PHP_EOL . PHP_EOL;
        }

        return $results;
    }

    private function getShops() {
        return $this->modelManager->getRepository(Shop::class)->findBy(['active' => true]);
    }

    private function getConfigForShop(Shop $shop) {
        return $this->configReader->getByPluginName('VerignAiPhilosSearch', $shop);
    }

    private function mapLocale(Locale $locale) {
        return $this->localeMapper->mapLocaleString($locale->getLocale());
    }

    private function updateDB(Shop $shop, array $config) {
        $this->aiRepository->setPriceGroup($shop->getCustomerGroup()->getKey());
        $this->aiRepository->setLocale($shop->getLocale()->getLocale());
        $this->aiRepository->setPluginConfig($config);


        try {
            $existingArticles = $this->aiRepository->getArticles();
        } catch (\Exception $e) {
            $existingArticles = [];
        }

        $existingIds = [];
        foreach ($existingArticles as $existingArticle) {
            $id = intval($existingArticle['_id']);
            if ($id > 0) {
                $existingIds[] = $id;
            }
        }
        if (count($existingIds) > 0) {
            $this->aiRepository->updateArticles($existingIds);
        }

        $newIds = $this->getArticleIds($existingIds);
        $response = $this->aiRepository->createArticles($newIds);

    }

    private function getArticleIds(array $excludedIds = []) {
        $qb = $this->modelManager->getRepository(Detail::class)
            ->createQueryBuilder('d')
            ->select('d.id');

        if (count($excludedIds) > 0) {
            $qb->where('d.id NOT IN ( :excludedIds )')
                ->setParameter('excludedIds', $excludedIds, Connection::PARAM_INT_ARRAY);
        }

        $ids = $qb->getQuery()
            ->getArrayResult();

        $ids = array_map('current', $ids);

        return $ids;
    }
}