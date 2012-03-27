<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Shell
 * @copyright   Copyright (c) 2012 Thomas Birke
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once 'abstract.php';

/**
 * Magento Compiler Shell Script
 *
 * @category    Mage
 * @package     Mage_Shell
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Mage_Shell_CopyStoreData extends Mage_Shell_Abstract
{
    protected $connection;

    /**
     * Run script
     *
     */
    public function run()
    {
        if ($this->getArg('source')) {
            $sourceId = $this->getArg('source');
            $targetId = $this->getArg('target');
            $tables = array(
                'catalog_product_entity_datetime',
                'catalog_product_entity_decimal',
                'catalog_product_entity_gallery',
                'catalog_product_entity_int',
                'catalog_product_entity_text',
                'catalog_product_entity_varchar'
            );
            $this->connection = Mage::getSingleton('core/resource')->getConnection('core_write');

            /* copy config of store and its associated website */
            $sourceWebsiteId = Mage::getModel('core/store')->load($sourceId)->getWebsiteId();
            $targetWebsiteId = Mage::getModel('core/store')->load($targetId)->getWebsiteId();
            if ($sourceWebsiteId != $targetWebsiteId) {
                $this->copyConfig('websites', $sourceWebsiteId, $targetWebsiteId);
            }
            $this->copyConfig('store', $sourceId, $targetId);

            /* copy product data */
            foreach ($tables as $table=>$pkey) {
                echo $table;

                $query1 = sprintf('CREATE TEMPORARY TABLE tmp_%s SELECT * FROM %s WHERE store_id=%d;', $table, $table, $sourceId);
                $query2 = sprintf('UPDATE tmp_%s SET value_id=NULL, store_id=%d;', $table, $targetId);
                $query3 = sprintf('INSERT INTO %s SELECT * FROM tmp_%s;', $table, $table);

                $this->query($query1);
                $updates = $this->query($query2);
                echo  ': ', $updates->rowCount(), ' rows', PHP_EOL;
                $this->query($query3);
            }
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * copy store configuration
     * 
     * @param string $scope    ("websites" or "store")
     * @param int    $sourceId
     * @param int    $targetId 
     * @return void
     */
    protected function copyConfig($scope, $sourceId, $targetId)
    {
        echo 'core_config_data (scope ', $scope, ' ', $sourceId, '->', $targetId, ')';
        $query1 = sprintf('CREATE TEMPORARY TABLE tmp_core_config_data SELECT * FROM core_config_data WHERE scope="%s" AND scope_id= %d;', $scope, $sourceId);
        $query2 = sprintf('UPDATE tmp_core_config_data SET config_id=NULL, scope_id=%d;', $targetId);
        $query3 = sprintf('INSERT INTO core_config_data SELECT * FROM tmp_core_config_data;');

        $this->query($query1);
        $updates = $this->query($query2);
        echo  ': ', $updates->rowCount(), ' rows', PHP_EOL;
        $this->query($query3);
        $this->query('DROP TEMPORARY TABLE tmp_core_config_data');
    }

    /**
     * execute db query
     * 
     * @param string $query
     * @return Zend_Db_Statement_Pdo
     */
    protected function query($query)
    {
        try {
            return $this->connection->query($query);
        } catch (\Exception $e) {
            echo PHP_EOL, 'Failed execute ', $query, PHP_EOL;
            echo $e->getMessage();
            exit(1);
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f copyStoreData.php -- --source <sourceId> --target <targetId>
  <sourceId> id of the store you want to copy from
  <targetId> id of the store you want to copy to

USAGE;
    }
}

$shell = new Mage_Shell_CopyStoreData();
$shell->run();

