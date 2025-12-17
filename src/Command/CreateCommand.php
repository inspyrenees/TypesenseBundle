<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Command;

use ACSEO\TypesenseBundle\Manager\CollectionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'typesense:create',
    description: 'Create Typesense indexes'
)]
class CreateCommand extends Command
{
    public function __construct(
        private readonly CollectionManager $collectionManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('indexes', null, InputOption::VALUE_OPTIONAL, 'The index(es) to repopulate. Comma separated values')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $collectionDefinitions = $this->collectionManager->getCollectionDefinitions();
        $indexes = (null !== $indexes = $input->getOption('indexes')) ? explode(',', $indexes) : \array_keys($collectionDefinitions);

        foreach ($indexes as $index) {
            if (!isset($collectionDefinitions[$index])) {
                $io->error('Unable to find index "'.$index.'" in collection definition (available : '.implode(', ', array_keys($collectionDefinitions)).')');

                return Command::INVALID;
            }
        }

        // filter collection definitions
        $collectionDefinitions = array_filter($collectionDefinitions, function ($key) use ($indexes) {
            return \in_array($key, $indexes, true);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($collectionDefinitions as $name => $def) {
            $name = $def['name'];
            $typesenseName = $def['typesense_name'];
            try {
                $output->writeln(sprintf('<info>Deleting</info> <comment>%s</comment> (<comment>%s</comment> in Typesense)', $name, $typesenseName));
                $this->collectionManager->deleteCollection($name);
            } catch (\Typesense\Exceptions\ObjectNotFound $exception) {
                $output->writeln(sprintf('Collection <comment>%s</comment> <info>does not exists</info> ', $typesenseName));
            }

            $output->writeln(sprintf('<info>Creating</info> <comment>%s</comment> (<comment>%s</comment> in Typesense)', $name, $typesenseName));
            $this->collectionManager->createCollection($name);
        }

        return Command::SUCCESS;
    }
}
