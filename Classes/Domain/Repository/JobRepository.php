<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<\Agentur\SmartJobFinder\Domain\Model\Job>
 */
class JobRepository extends Repository
{
    private const TABLE = 'tx_smartjobfinder_domain_model_job';

    protected $defaultOrderings = [
        'featured' => QueryInterface::ORDER_DESCENDING,
        'crdate' => QueryInterface::ORDER_DESCENDING,
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * @param array{
     *     q?: string,
     *     location?: string,
     *     employmentType?: string,
     *     workplaceType?: string,
     *     category?: int|string
     * } $filter
     * @return QueryResultInterface<\Agentur\SmartJobFinder\Domain\Model\Job>
     */
    public function findByFilter(array $filter, int $limit = 0, int $offset = 0): QueryResultInterface
    {
        $query = $this->buildFilterQuery($filter);
        if ($limit > 0) {
            $query->setLimit($limit);
        }
        if ($offset > 0) {
            $query->setOffset($offset);
        }

        return $query->execute();
    }

    /**
     * @param array<string, mixed> $filter
     */
    public function countByFilter(array $filter): int
    {
        return $this->buildFilterQuery($filter)->count();
    }

    /**
     * One SQL DISTINCT — never hydrate Extbase objects for a dropdown.
     *
     * @return list<string>
     */
    public function findDistinctLocations(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->selectLiteral('DISTINCT ' . $queryBuilder->quoteIdentifier('location'))
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->neq('location', $queryBuilder->createNamedParameter('')),
            )
            ->orderBy('location', 'ASC');

        $this->applyNotExpired($queryBuilder);
        $this->applyStoragePid($queryBuilder);

        $locations = [];
        foreach ($queryBuilder->executeQuery()->fetchFirstColumn() as $location) {
            $location = trim((string)$location);
            if ($location !== '') {
                $locations[] = $location;
            }
        }

        return $locations;
    }

    /**
     * Single JOIN instead of N+1 getCompany() calls in the list view.
     *
     * @param list<int> $jobUids
     * @return array<int, string>
     */
    public function findCompanyNamesByJobUids(array $jobUids): array
    {
        $jobUids = array_values(array_unique(array_filter(array_map('intval', $jobUids))));
        if ($jobUids === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->select('job.uid', 'company.name')
            ->from(self::TABLE, 'job')
            ->leftJoin(
                'job',
                'tx_smartjobfinder_domain_model_company',
                'company',
                'company.uid = job.company',
            )
            ->where(
                $queryBuilder->expr()->in('job.uid', implode(',', $jobUids)),
            );

        $names = [];
        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
            $name = trim((string)($row['name'] ?? ''));
            if ($name !== '') {
                $names[(int)$row['uid']] = $name;
            }
        }

        return $names;
    }

    /**
     * @param array<string, mixed> $filter
     */
    private function buildFilterQuery(array $filter): QueryInterface
    {
        $query = $this->createQuery();
        $constraints = [];
        $now = time();
        $constraints[] = $query->logicalOr(
            $query->equals('validThrough', 0),
            $query->greaterThanOrEqual('validThrough', $now),
        );

        $search = trim((string)($filter['q'] ?? ''));
        if ($search !== '') {
            $constraints[] = $this->searchConstraint($query, $search);
        }

        $location = trim((string)($filter['location'] ?? ''));
        if ($location !== '') {
            $constraints[] = $query->equals('location', $location);
        }

        $employmentType = trim((string)($filter['employmentType'] ?? ''));
        if ($employmentType !== '') {
            $constraints[] = $query->equals('employmentType', $employmentType);
        }

        $workplaceType = trim((string)($filter['workplaceType'] ?? ''));
        if ($workplaceType !== '') {
            $constraints[] = $query->equals('workplaceType', $workplaceType);
        }

        $category = (int)($filter['category'] ?? 0);
        if ($category > 0) {
            $constraints[] = $query->equals('categories.uid', $category);
        }

        if ($constraints !== []) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        return $query;
    }

    private function searchConstraint(QueryInterface $query, string $search): object
    {
        $fullTextUids = $this->searchUidsViaFullText($search);
        if ($fullTextUids !== null) {
            return $fullTextUids === []
                ? $query->equals('uid', 0)
                : $query->in('uid', $fullTextUids);
        }

        $like = '%' . addcslashes($search, '%_\\') . '%';

        return $query->logicalOr(
            $query->like('title', $like),
            $query->like('teaser', $like),
            $query->like('location', $like),
            $query->like('department', $like),
        );
    }

    /**
     * @return list<int>|null null = fulltext not available, use LIKE
     */
    private function searchUidsViaFullText(string $search): ?array
    {
        if (mb_strlen($search) < 3 || !$this->supportsMysqlFullText()) {
            return null;
        }

        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        try {
            $sql = 'SELECT uid FROM ' . self::TABLE
                . ' WHERE MATCH (title, teaser, department, location) AGAINST (:q IN NATURAL LANGUAGE MODE)'
                . ' AND hidden = 0 AND deleted = 0'
                . ' AND (valid_through = 0 OR valid_through >= :now)'
                . ' LIMIT 500';
            $uids = [];
            foreach ($connection->executeQuery($sql, ['q' => $search, 'now' => time()])->fetchFirstColumn() as $uid) {
                $uids[] = (int)$uid;
            }

            return $uids;
        } catch (\Throwable) {
            return null;
        }
    }

    private function supportsMysqlFullText(): bool
    {
        $driver = $this->connectionPool->getConnectionForTable(self::TABLE)->getParams()['driver'] ?? '';

        return in_array($driver, ['mysqli', 'pdo_mysql'], true);
    }

    private function applyNotExpired(QueryBuilder $queryBuilder): void
    {
        $now = time();
        $queryBuilder->andWhere(
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->eq('valid_through', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->gte('valid_through', $queryBuilder->createNamedParameter($now, Connection::PARAM_INT)),
            ),
        );
    }

    private function applyStoragePid(QueryBuilder $queryBuilder): void
    {
        $storagePageIds = $this->createQuery()->getQuerySettings()->getStoragePageIds();
        $storagePageIds = array_map('intval', $storagePageIds);
        if ($storagePageIds === []) {
            return;
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->in('pid', implode(',', $storagePageIds)),
        );
    }
}
