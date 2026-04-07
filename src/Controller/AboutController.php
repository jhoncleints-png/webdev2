<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_about')]
    public function index(): Response
    {
        $teamMembers = [
            [
                'name' => 'Jhon Cleint Niño Y. Samaco',
                'position' => 'Founder & Head Brewmaster',
                'photo' => 'images/team/jhon.jpg',
                'description' => 'Passionate about crafting the finest Filipino craft beers with over 10 years of brewing experience.',
            ],
            [
                'name' => 'Maria Santos',
                'position' => 'Operations Manager',
                'photo' => 'images/team/placeholder.jpg',
                'description' => 'Ensures smooth day-to-day operations and quality control in our brewery.',
            ],
            [
                'name' => 'Carlos Reyes',
                'position' => 'Sales & Marketing Lead',
                'photo' => 'images/team/placeholder.jpg',
                'description' => 'Dedicated to bringing our craft beers to every corner of the Philippines.',
            ],
        ];

        return $this->render('about/index.html.twig', [
            'team_members' => $teamMembers,
        ]);
    }
}
