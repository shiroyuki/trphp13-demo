<?php
namespace Shiroyuki\SandboxBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CaseZeroCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sandbox:case0')
            ->setDescription('Case One')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em   = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('Shiroyuki\SandboxBundle\Entity\Node');

        $n1 = $repo->findOneBy(array('id' => 1));
        $n2 = $repo->findOneBy(array('id' => 2));

        $n1->setName('A');
        $n2->setName('B');

        $em->persist($n1);
        $em->flush();
    }
}