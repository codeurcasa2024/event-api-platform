<?php
// tests/Functional/EventTest.php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Doctrine\ORM\Tools\SchemaTool;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

class EventTest extends ApiTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get('doctrine')->getManager();

        // Recréation du schéma
        $tool = new SchemaTool($em);
        $classes = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($classes);
        $tool->createSchema($classes);

        // Hashing
        $hasher = new NativePasswordHasher();

        // Utilisateur qui se loggue
        $user1 = new User();
        $user1->setEmail('test@example.com');
        $user1->setFullName('Test User');
        $user1->setRoles(['ROLE_USER']);
        $user1->setPassword($hasher->hash('password'));
        $em->persist($user1);

        // Utilisateur invité
        $user2 = new User();
        $user2->setEmail('invitee@example.com');
        $user2->setFullName('Invitee User');
        $user2->setRoles(['ROLE_USER']);
        $user2->setPassword($hasher->hash('password'));
        $em->persist($user2);

        $em->flush();
    }

    public function testEventCrudLifecycle(): void
    {
        $client = static::createClient();

        // 1. Authenticate
        $response = $client->request('POST', '/login', [
            'headers' => ['Accept' => 'application/ld+json'],
            'json' => ['email' => 'test@example.com', 'password' => 'password']
        ]);
        $this->assertResponseIsSuccessful();
        $token = $response->toArray()['token'];

        // 2. Create Event
        $response = $client->request('POST', '/api/events', [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => [
                'title' => 'Test Event',
                'description' => 'Testing CRUD',
                'startsAt' => '2025-06-15T10:00:00+00:00',
                'endsAt' => '2025-06-15T12:00:00+00:00',
                'createdBy' => '/api/users/1'
            ]
        ]);
        $this->assertResponseStatusCodeSame(201);
        $event = $response->toArray();
        $this->assertArrayHasKey('id', $event);

        // 3. Read Event
        $client->request('GET', '/api/events/'.$event['id'], [
            'headers' => ['Authorization' => 'Bearer '.$token]
        ]);
        $this->assertResponseIsSuccessful();

        // 4. Update Event
        $client->request('PUT', '/api/events/'.$event['id'], [
            'headers' => ['Authorization' => 'Bearer '.$token],
            'json' => [
                'title' => 'Updated Event',
                'description' => 'Updated description',
                'startsAt' => '2025-06-15T11:00:00+00:00',
                'endsAt' => '2025-06-15T13:00:00+00:00',
                'createdBy' => '/api/users/1'
            ]
        ]);
        $this->assertResponseIsSuccessful();

        // 5. Delete Event
        $client->request('DELETE', '/api/events/'.$event['id'], [
            'headers' => ['Authorization' => 'Bearer '.$token]
        ]);
        $this->assertResponseStatusCodeSame(204);
    }
}
