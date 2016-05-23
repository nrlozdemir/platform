<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class CalendarEventControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData']);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_calendar_event_index'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Calendar events - Activities', $crawler->filter('#page-title')->html());
    }

    /**
     * @return int
     */
    public function testCreateAction()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_calendar_event_create'));
        $form    = $crawler->selectButton('Save and Close')->form();
        $user    = $this->getReference('simple_user');
        $admin   = $this->getAdminUser();

        $form['oro_calendar_event_form[title]']       = 'test title';
        $form['oro_calendar_event_form[description]'] = 'test description';
        $form['oro_calendar_event_form[start]']       = '2016-05-23T14:46:02Z';
        $form['oro_calendar_event_form[end]']         = '2016-05-23T15:46:02Z';
        $form['oro_calendar_event_form[attendees]']   = sprintf('%s, %s', $user->getId(), $admin->getId());

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Calendar event saved', $crawler->html());

        $attendees = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:Attendee')
            ->findAll();
        $this->assertCount(2, $attendees);

        $attendeesId = [];
        foreach ($attendees as $attendee) {
            $attendeesId[] = $attendee->getId();
        }

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['title' => 'test title']);

        return [
            'calendarId' => $calendarEvent->getId(),
            'attendees'  => $attendeesId
        ];
    }

    /**
     * @depends testCreateAction
     *
     * @param array $param
     */
    public function testViewAction(array $param)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_calendar_event_view', ['id' => $param['calendarId']])
        );
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testCreateAction
     *
     * @param array $param
     */
    public function testUpdateAction(array $param)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_calendar_event_update', ['id' => $param['calendarId']])
        );
        $form    = $crawler->selectButton('Save and Close')->form();
        $user    = $this->getReference('simple_user');
        $admin   = $this->getAdminUser();

        $form['oro_calendar_event_form[title]']       = 'test title';
        $form['oro_calendar_event_form[description]'] = 'test description';
        $form['oro_calendar_event_form[start]']       = '2016-05-23T14:46:02Z';
        $form['oro_calendar_event_form[end]']         = '2016-05-23T15:46:02Z';
        $form['oro_calendar_event_form[attendees]']   = sprintf('%s, %s', $user->getId(), $admin->getId());

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Calendar event saved', $crawler->html());

        $attendees = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:Attendee')
            ->findAll();
        $this->assertCount(2, $attendees);

        foreach ($attendees as $attendee) {
            $this->assertTrue(
                in_array($attendee->getId(), $param['attendees'])
            );
        }
    }

    /**
     * @return User
     */
    protected function getAdminUser()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroUserBundle:User')
            ->findOneByEmail('admin@example.com');
    }
}
