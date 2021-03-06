<?php
/**
 * Created by PhpStorm.
 * User: sl
 * Date: 09.10.17
 * Time: 16:17
 */

namespace AiphilosSearch\Components\Helpers;

/**
 * Interface LocaleStringMapperInterface
 *
 * This interface is to be implemented by all classes which map
 * Shopware formatted locale strings to the format used by the API
 * eg. de_DE -> de-de
 *
 * It is that simple... for now
 *
 * @package AiphilosSearch\Components\Helpers
 */
interface LocaleStringMapperInterface
{
    /**
     * @param string $localeString
     * @return string
     */
    public function mapLocaleString($localeString);
}