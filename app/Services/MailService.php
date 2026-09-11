<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;

class MailService
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }
}
