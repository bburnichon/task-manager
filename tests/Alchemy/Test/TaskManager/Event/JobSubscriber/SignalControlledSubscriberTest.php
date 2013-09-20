<?php

namespace Alchemy\Test\TaskManager\Event\JobSubscriber;

use Alchemy\TaskManager\Event\JobSubscriber\SignalControlledSubscriber;
use Alchemy\TaskManager\Event\JobEvent;
use Neutron\SignalHandler\SignalHandler;

class SignalControlledSubscriberTest extends SubscriberTestCase
{
    /**
     * @dataProvider provideInvalidPeriods
     * @expectedException Alchemy\TaskManager\Exception\InvalidArgumentException
     * @expectedExceptionMessage Signal period should be greater than 0.15 s.
     */
    public function testWithInvalidPeriod($limit)
    {
        new SignalControlledSubscriber(SignalHandler::getInstance(), $limit);
    }

    public function provideInvalidPeriods()
    {
        return array(array(0.10), array(-5));
    }

    public function testDoesNothingIfNotStarted()
    {
        $job = $this->getMock('Alchemy\TaskManager\JobInterface');
        $job->expects($this->never())->method('stop');
        $job->expects($this->any())->method('isStarted')->will($this->returnValue(true));

        $subscriber = new SignalControlledSubscriber(SignalHandler::getInstance(), 0.15);
        $subscriber->onJobTick(new JobEvent($job));
    }

    public function testWithLogger()
    {
        $job = $this->getMock('Alchemy\TaskManager\JobInterface');
        $job->expects($this->once())->method('stop');
        $job->expects($this->any())->method('isStarted')->will($this->returnValue(true));

        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $logger->expects($this->once())->method('info')->with('No signal received since start-time (max period is 0.15 s.), stopping.');

        $subscriber = new SignalControlledSubscriber(SignalHandler::getInstance(), 0.15, $logger);
        $subscriber->onJobStart(new JobEvent($job));
        usleep(150000);
        $subscriber->onJobTick(new JobEvent($job));
    }

    public function testOnJobTickWithoutLogger()
    {
        $job = $this->getMock('Alchemy\TaskManager\JobInterface');
        $job->expects($this->once())->method('stop');
        $job->expects($this->any())->method('isStarted')->will($this->returnValue(true));

        $subscriber = new SignalControlledSubscriber(SignalHandler::getInstance(), 0.15);
        $subscriber->onJobStart(new JobEvent($job));
        usleep(150000);
        $subscriber->onJobTick(new JobEvent($job));
    }

    public function testOnJobTickMultiple()
    {
        $job = $this->getMock('Alchemy\TaskManager\JobInterface');
        $job->expects($this->never())->method('stop');
        $job->expects($this->any())->method('isStarted')->will($this->returnValue(true));

        $subscriber = new SignalControlledSubscriber(SignalHandler::getInstance(), 0.15);
        $subscriber->onJobStart(new JobEvent($job));
        usleep(100000);
        $subscriber->signalHandler(SIGCONT);
        $subscriber->onJobTick(new JobEvent($job));
        usleep(100000);
        $subscriber->signalHandler(SIGCONT);
        $subscriber->onJobTick(new JobEvent($job));
        usleep(100000);
        $subscriber->signalHandler(SIGCONT);
        $subscriber->onJobTick(new JobEvent($job));
        usleep(100000);
        $subscriber->signalHandler(SIGCONT);
        $subscriber->onJobTick(new JobEvent($job));
        usleep(100000);
        $subscriber->signalHandler(SIGCONT);
        $subscriber->onJobTick(new JobEvent($job));
    }

    public function testOnJobTickDoesNothingIfJobIsNotStarted()
    {
        $job = $this->getMock('Alchemy\TaskManager\JobInterface');
        $job->expects($this->never())->method('stop');
        $job->expects($this->any())->method('isStarted')->will($this->returnValue(false));

        $subscriber = new SignalControlledSubscriber(SignalHandler::getInstance(), 0.15);
        $subscriber->onJobStart(new JobEvent($job));
        usleep(150000);
        $subscriber->onJobTick(new JobEvent($job));
    }

    public function testOnJobTickWhenMemoryIsQuiteOk()
    {
        $job = $this->getMock('Alchemy\TaskManager\JobInterface');
        $job->expects($this->never())->method('stop');
        $job->expects($this->any())->method('isStarted')->will($this->returnValue(false));

        $subscriber = new SignalControlledSubscriber(SignalHandler::getInstance(), 0.15);
        $subscriber->onJobStart(new JobEvent($job));
        usleep(100000);
        $subscriber->onJobTick(new JobEvent($job));
    }

    protected function getSubscriber()
    {
        return new SignalControlledSubscriber(SignalHandler::getInstance());
    }
}