<?php

namespace Pim\Bundle\ImportExportBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Oro\Bundle\BatchBundle\Event\JobExecutionEvent;
use Oro\Bundle\BatchBundle\Event\EventInterface;
use Pim\Bundle\ImportExportBundle\Archiver\ArchiverInterface;
use Oro\Bundle\BatchBundle\Entity\JobExecution;

/**
 * Job execution archivist
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class JobExecutionArchivist implements EventSubscriberInterface
{
    /** @var array */
    protected $archivers = array();

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            EventInterface::AFTER_JOB_EXECUTION => 'afterJobExecution',
        );
    }

    /**
     * Register an archiver
     *
     * @param ArchiveInterface $archiver
     */
    public function registerArchiver(ArchiverInterface $archiver)
    {
        if (array_key_exists($archiver->getName(), $this->archivers)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'There is already a registered archiver named "%s": %s',
                    $archiver->getName(),
                    get_class($this->archivers[$archiver->getName()])
                )
            );
        }

        $this->archivers[$archiver->getName()] = $archiver;
    }

    /**
     * Delegate archiving to the registered archivers
     *
     * @param JobExecutionEvent $event
     */
    public function afterJobExecution(JobExecutionEvent $event)
    {
        $jobExecution = $event->getJobExecution();

        foreach ($this->archivers as $archiver) {
            $archiver->archive($jobExecution);
        }
    }

    /**
     * Get the archives generated by the archivers
     *
     * @param JobExecution $jobExecution
     *
     * @return array
     */
    public function getArchives(JobExecution $jobExecution)
    {
        $archives = array();

        foreach ($this->archivers as $archiver) {
            $archives[$archiver->getName()] = $archiver->getArchives($jobExecution);
        }

        return $archives;
    }

    /**
     * Get an archive of an archiver
     *
     * @param JobExecution $jobExecution
     * @param string       $archiver
     * @param string       $key
     *
     * @return \Gaufrette\Stream
     */
    public function getArchive(JobExecution $jobExecution, $archiver, $key)
    {
        if (!isset($this->archivers[$archiver])) {
            throw new \InvalidArgumentException(
                sprintf('Archiver "%s" is not registered', $archiver)
            );
        }

        return $this->archivers[$archiver]->getArchive($jobExecution, $key);
    }
}
