<?php
/**
 * Created by PhpStorm.
 * User: sl
 * Date: 10.11.17
 * Time: 11:15
 */

namespace VerignAiPhilosSearch\Bundle\AiPhilosSearchBundle\Repositories\AiPhilos;

interface ArticleRepositoryInterface
{
    /**
     * @param array $pluginConfig
     * @return $this
     */
    public function setPluginConfig(array $pluginConfig);

    /**
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale);

    /**
     * @param string $priceGroup
     * @return BasicArticleRepository
     */
    public function setPriceGroup($priceGroup);

    public function createArticle($articleId);

    public function getArticle($articleId);

    public function updateArticle($articleId);

    public function deleteArticle($articleId);

    public function createArticles(array $articleIds);

    public function getArticles();

    public function updateArticles(array $articleIds);

    public function deleteArticles(array $articleIds);
}