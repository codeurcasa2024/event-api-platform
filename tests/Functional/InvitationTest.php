<?php
// tests/Functional/InvitationTest.php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Doctrine\ORM\Tools\SchemaTool;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

class InvitationTest extends ApiTestCase
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

        // Utilisateur créateur
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

    public function testInvitationLifecycle(): void
    {
        $client = static::createClient();

        // Authenticate as creator
        $response = $client->request('POST', '/login', [
            'headers' => ['Accept' => 'application/ld+json'],
            'json' => ['email' => 'test@example.com', 'password' => 'password']
        ]);
        $tokenCreator = $response->toArray()['token'];

        // Create Event
        $response = $client->request('POST', '/api/events', [
            'headers' => ['Authorization' => 'Bearer '.$tokenCreator],
            'json' => [
                'title' => 'Invite Test',
                'description' => 'Invitation flow',
                'startsAt' => '2025-07-01T10:00:00+00:00',
                'endsAt' => '2025-07-01T12:00:00+00:00',
                'createdBy' => '/api/users/1'
            ]
        ]);
        $eventId = $response->toArray()['id'];

        // Seed invitee user in DB fixture: /api/users/2

        // Create Invitation as creator
        $response = $client->request('POST', '/api/invitations', [
            'headers' => ['Authorization' => 'Bearer '.$tokenCreator],
            'json' => [
                'event' => "/api/events/$eventId",
                'invitee' => '/api/users/2',
                'status' => 'pending'
            ]
        ]);
        $this->assertResponseStatusCodeSame(201);
        $invitation = $response->toArray();

// Authenticate as invitee
$response = $client->request('POST', '/login', [
    'headers' => ['Accept' => 'application/ld+json'],
    'json'    => ['email' => 'invitee@example.com', 'password' => 'password']
]);
$this->assertResponseIsSuccessful(); // maintenant le login doit réussir
$tokenInvitee = $response->toArray()['token'];

        // Accept Invitation via PATCH
        $response = $client->request('PATCH', '/api/invitations/'.$invitation['id'], [
            'headers' => [
                'Authorization' => 'Bearer '.$tokenInvitee,
                'Content-Type' => 'application/merge-patch+json'
            ],
            'json' => ['status' => 'accepted']
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertEquals('accepted', $response->toArray()['status']);

        // List only own invitations
        $response = $client->request('GET', '/api/invitations', [
            'headers' => ['Authorization' => 'Bearer '.$tokenInvitee]
        ]);
        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
    }
}