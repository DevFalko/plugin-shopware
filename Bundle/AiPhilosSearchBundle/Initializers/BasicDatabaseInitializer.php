<?php
/**
 * Created by PhpStorm.
 * User: sl
 * Date: 05.10.17
 * Time: 11:39
 */

namespace VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Initializers;


use Aiphilos\Api\Items\ClientInterface;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Schemes\ArticleSchemeInterface;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Traits\ApiUserTrait;

/**
 * Class BasicDatabaseInitializer
 *
 * This implementation of the DatabaseInitializerInterface
 * creates or updates the Scheme of the API database for the provided shop configuration
 * it performs no checks on whether this shop should actually use the API,
 * this check is to be performed by the consumers/users of this class.
 *
 * @package VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Initializers
 */
class BasicDatabaseInitializer implements DatabaseInitializerInterface
{
    use ApiUserTrait;

    /** @var ArticleSchemeInterface */
    private $scheme;

    /**
     * DatabaseInitializer constructor.
     * @param ClientInterface $itemClient
     * @param ArticleSchemeInterface $scheme
     * @param \Zend_Cache_Core $cache
     */
    public function __construct(ClientInterface $itemClient, ArticleSchemeInterface $scheme, \Zend_Cache_Core $cache) {
        $this->itemClient = $itemClient;
        $this->cache = $cache;
        $this->scheme = $scheme;
    }

    public function createOrUpdateScheme($language, array $pluginConfig) {
        $this->pluginConfig = $pluginConfig;
        $this->setAuthentication();
        if (!$this->validateLanguage($language)) {
            return CreateResultEnum::LANGUAGE_NOT_SUPPORTED;
        }

        $this->itemClient->setDefaultLanguage($language);

        try {
            $this->setDbName();
        } catch (\Exception $e) {
            return CreateResultEnum::NAME_ERROR;
        }

        try {
            $dbExists = $this->itemClient->checkDatabaseExists();
        } catch (\Exception $e) {
            $dbExists = false;
        }

        try {
            //Scheme should be set in any successful case
            $this->itemClient->setScheme($this->scheme->getScheme());
        } catch (\Exception $e) {
            return CreateResultEnum::SCHEME_ERROR;
        }

        return  $dbExists ? CreateResultEnum::ALREADY_EXISTS : CreateResultEnum::CREATED;

    }


}