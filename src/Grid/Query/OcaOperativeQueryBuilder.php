<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace RgOcaEpak\Grid\Query;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

final class OcaOperativeQueryBuilder extends AbstractDoctrineQueryBuilder
{
    /**
     * @var int
     */
    private $contextLangId;

    /**
     * @var int
     */
    private $contextShopId;

    /**
     * @param string $dbPrefix
     * @param int $contextLangId
     * @param int $contextShopId
     */
    public function __construct(Connection $connection, $dbPrefix, $contextLangId, $contextShopId)
    {
        parent::__construct($connection, $dbPrefix);

        $this->contextLangId = $contextLangId;
        $this->contextShopId = $contextShopId;
    }

    // Get Search query builder returns a QueryBuilder that is used to fetch filtered, sorted and paginated data from the database.
    // This query builder is also used to get the SQL query that was executed.
    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getBaseQuery();
        if ($searchCriteria->getOrderBy()) {
            $qb->select('*')
                ->orderBy(
                    $searchCriteria->getOrderBy(),
                    $searchCriteria->getOrderWay()
                )
                ->setFirstResult($searchCriteria->getOffset())
                ->setMaxResults($searchCriteria->getLimit());
        } else {
            $qb->select('*');
        }
        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ('id_ocae_operatives' === $filterName) {
                $qb->andWhere("o.id_ocae_operatives = :$filterName");
                $qb->setParameter($filterName, $filterValue);

                continue;
            }

            $qb->andWhere("$filterName LIKE :$filterName");
            $qb->setParameter($filterName, '%' . $filterValue . '%');
        }

        return $qb;
    }

    // Get Count query builder that is used to get the total count of all records (products)
    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getBaseQuery();
        $qb->select('COUNT(o.id_ocae_operatives)');

        return $qb;
    }

    // Base query can be used for both Search and Count query builders
    private function getBaseQuery()
    {
        return $this->connection
            ->createQueryBuilder()
            ->from($this->dbPrefix . 'ocae_operatives', 'o')
            ->setParameter('context_lang_id', $this->contextLangId)
            ->setParameter('context_shop_id', $this->contextShopId);
    }
}
