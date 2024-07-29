<?php

declare(strict_types=1);

namespace JulianHofmann\Sitepackage\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand('sitepackage:ensure:translation')]
final class EnsureTranslationCommand extends Command
{
    public function __construct(
        private ConnectionPool $connectionPool,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $languages = [1,2];
        Bootstrap::initializeBackendAuthentication();;
        foreach ($this->getPages() as $page) {
            if ($page['uid'] === 0) {
                continue;
            }
            foreach ($languages as $langId) {
                $output->write(sprintf('> Transalte UID %s to language %s ... ', $page['uid'], $langId));
                $cmd = [];
                $cmd['pages'][$page['uid']]['localize'] = $langId;
                /** @var DataHandler $dataHandler */
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start([], $cmd);
                $dataHandler->process_cmdmap();
                if ($dataHandler->errorLog === []) {
                    $output->writeln('ok');
                    continue;
                }
                $output->writeln('failed !');
                $output->writeln(implode(PHP_EOL, $dataHandler->errorLog));
            }
        }
        $output->write('> Make all pages visible ... ');
        $updated = $this->makeAllPagesVisible();
        $output->writeln(sprintf('%s records updated.', $updated));
        return Command::SUCCESS;
    }

    private function getPages(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder
            ->select(...array_values(['uid', 'pid']))
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', '0'),
                $queryBuilder->expr()->eq('t3ver_wsid', '0'),
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function makeAllPagesVisible(): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder
            ->update('pages')
            ->set('hidden', 0, true)
            ->where(
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->in('sys_language_uid', [1,2]),
            )
            ->executeStatement();
    }
}
