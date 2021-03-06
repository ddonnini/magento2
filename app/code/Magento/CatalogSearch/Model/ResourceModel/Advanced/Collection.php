<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogSearch\Model\ResourceModel\Advanced;

use Magento\Catalog\Model\Product;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Search\Adapter\Mysql\TemporaryStorage;

/**
 * Collection Advanced
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /**
     * List Of filters
     * @var array
     */
    private $filters = [];

    /**
     * @var \Magento\Search\Api\SearchInterface
     */
    private $search;

    /**
     * @var \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory
     */
    private $temporaryStorageFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * Collection constructor.
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Validator\UniversalFactory $universalFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Module\Manager $moduleManager ,
     * @param \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Url $catalogUrl
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Customer\Api\GroupManagementInterface $groupManagement
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitation $productLimitation
     * @param \Magento\Search\Api\SearchInterface $search
     * @param \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory $temporaryStorageFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitation $productLimitation,
        \Magento\Search\Api\SearchInterface $search,
        \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory $temporaryStorageFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null
    ) {
        $this->search = $search;
        $this->temporaryStorageFactory = $temporaryStorageFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            $productLimitation,
            $connection
        );
    }

    /**
     * Add not indexable fields to search
     *
     * @param array $fields
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addFieldsToFilter($fields)
    {
        if ($fields) {
            $this->filters = array_merge($this->filters, $fields);
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _renderFiltersBefore()
    {
        if ($this->filters) {
            foreach ($this->filters as $attributes) {
                foreach ($attributes as $attributeCode => $attributeValue) {
                    $attributeCode = $this->getAttributeCode($attributeCode);
                    $this->addAttributeToSearch($attributeCode, $attributeValue);
                }
            }
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchCriteria->setRequestName('advanced_search_container');
            $searchResult = $this->search->search($searchCriteria);

            $temporaryStorage = $this->temporaryStorageFactory->create();
            $table = $temporaryStorage->storeApiDocuments($searchResult->getItems());

            $this->getSelect()->joinInner(
                [
                    'search_result' => $table->getName(),
                ],
                'e.entity_id = search_result.' . TemporaryStorage::FIELD_ENTITY_ID,
                []
            );
        }
        parent::_renderFiltersBefore();
    }

    /**
     * @param string $attributeCode
     * @return string
     */
    private function getAttributeCode($attributeCode)
    {
        if (is_numeric($attributeCode)) {
            $attributeCode = $this->_eavConfig->getAttribute(Product::ENTITY, $attributeCode)
                ->getAttributeCode();
        }

        return $attributeCode;
    }

    /**
     * Create a filter and add it to the SearchCriteriaBuilder.
     *
     * @param string $attributeCode
     * @param array|string $attributeValue
     * @return void
     */
    private function addAttributeToSearch($attributeCode, $attributeValue)
    {
        if (isset($attributeValue['from']) || isset($attributeValue['to'])) {
            $this->addRangeAttributeToSearch($attributeCode, $attributeValue);
        } elseif (!is_array($attributeValue)) {
            $this->filterBuilder->setField($attributeCode)->setValue($attributeValue);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        } elseif (isset($attributeValue['like'])) {
            $this->filterBuilder->setField($attributeCode)->setValue($attributeValue['like']);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        } elseif (isset($attributeValue['in'])) {
            $this->filterBuilder->setField($attributeCode)->setValue($attributeValue['in']);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        } elseif (isset($attributeValue['in_set'])) {
            $this->filterBuilder->setField($attributeCode)->setValue($attributeValue['in_set']);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        }
    }

    /**
     * Add attributes that have a range (from,to) to the SearchCriteriaBuilder.
     *
     * @param string $attributeCode
     * @param array|string $attributeValue
     * @return void
     */
    private function addRangeAttributeToSearch($attributeCode, $attributeValue)
    {
        if (isset($attributeValue['from']) && '' !== $attributeValue['from']) {
            $this->filterBuilder->setField("{$attributeCode}.from")->setValue($attributeValue['from']);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        }
        if (isset($attributeValue['to']) && '' !== $attributeValue['to']) {
            $this->filterBuilder->setField("{$attributeCode}.to")->setValue($attributeValue['to']);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        }
    }
}
