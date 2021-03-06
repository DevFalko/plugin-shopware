<?php
/**
 * Created by PhpStorm.
 * User: sl
 * Date: 10.11.17
 * Time: 15:32
 */

namespace AiphilosSearch\tests\Shopware;

use AiphilosSearch\Components\Repositories\Shopware\ArticleRepository;
use AiphilosSearch\tests\AbstractTestCase;

/**
 * Class ArticleRepositoryTest
 * @package AiphilosSearch\tests
 */
class ArticleRepositoryTest extends AbstractTestCase
{

    /**
     * @return null|ArticleRepository
     */
    public function testCanInstantiate() {
        $repo = null;
        $exception = null;

        try {
            $repo = new ArticleRepository(
                Shopware()->Db(),
                $this->getConfigReaderMock()
            );
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
        $this->assertInstanceOf(ArticleRepository::class, $repo);

        return $repo;
    }

    /**
     * @depends testCanInstantiate
     * @param ArticleRepository $repo
     * @return array|null
     */
    public function testGetArticleData(ArticleRepository $repo) {
        $exception = null;
        $articleData = null;

        try {
            $articleData = $repo->getArticleData();
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
        $this->assertInternalType('array', $articleData);
        $this->assertNotEmpty($articleData);

        return $articleData;
    }
}
