<?php

/*
 * This file is part of the SpBowerBundle package.
 *
 * (c) Martin Parsiegla <martin.parsiegla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sp\BowerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ListCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('sp:bower:list')
            ->setDescription('List all installed bower dependencies.')
            ->setHelp(<<<EOT
The <info>sp:bower:list</info> command lists all installed bower dependencies:

  <info>php app/console sp:bower:list</info>
EOT
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $formulaeBuilder \Sp\BowerBundle\Bower\FormulaeBuilder */
        $formulaeBuilder = $this->getContainer()->get('sp_bower.formulae_builder');
    }
} 