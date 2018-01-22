<?php
/**
 * Created by PhpStorm.
 * User: sl
 * Date: 05.10.17
 * Time: 10:55
 */

namespace VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Schemes;

use Aiphilos\Api\ContentTypesEnum;
use VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Repositories\Shopware\ArticleRepositoryInterface;

/**
 * Class BasicArticleScheme
 *
 * This implementation of the ArticleSchemeInterface provides a scheme
 * that matches the data that can be sensibly extracted from the default Shopware
 * article structure.
 *
 * @package VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Schemes
 */
class BasicArticleScheme implements ArticleSchemeInterface
{
    protected $scheme = [
        'ordernumber' => ContentTypesEnum::PRODUCT_NUMBER,
        'name' => ContentTypesEnum::PRODUCT_NAME,
        'description' => ContentTypesEnum::PRODUCT_DESCRIPTION,
        'description_long' => ContentTypesEnum::PRODUCT_DESCRIPTION,
        'keywords' => ContentTypesEnum::GENERAL_AUTO,
        'price' => ContentTypesEnum::PRODUCT_PRICE,
        'ean' => ContentTypesEnum::PRODUCT_GTIN,
        'supplier' => ContentTypesEnum::PRODUCT_MANUFACTURER,
        'sales' => ContentTypesEnum::ORDER_FREQUENCY,
        'points' => ContentTypesEnum::PRODUCT_RATING,
        'properties' => ContentTypesEnum::GENERAL_AUTO,
        'options' => ContentTypesEnum::GENERAL_AUTO,
        'attributes' => ContentTypesEnum::GENERAL_AUTO,
        'categories' => ContentTypesEnum::GENERAL_AUTO,
    ];


    /**
     * @return array
     */
    public function getScheme() {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getProductNumberKey() {
        return 'ordernumber';
    }



}