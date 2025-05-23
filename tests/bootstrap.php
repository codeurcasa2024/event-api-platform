<?php
// tests/bootstrap.php

use Symfony\Component\Dotenv\Dotenv;
use App\Kernel;
use Doctrine\ORM\Tools\SchemaTool;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

putenv('APP_ENV=test');
putenv('DATABASE_URL=sqlite:///:memory:');

$kernel = new Kernel('test', true);
$kernel->boot();
/** @var \Doctrine\ORM\EntityManagerInterface $em */
$em   = $kernel->getContainer()->get('doctrine')->getManager();

// Recréation du schéma
$tool = new SchemaTool($em);
$meta = $em->getMetadataFactory()->getAllMetadata();
$tool->dropSchema($meta);
$tool->createSchema($meta);

// Instanciation manuelle du hasher
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
$kernel->shutdown();
