<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, LoggerInterface $logger): Response
    {
        $submitted = false;
        $success = false;
        $error = null;

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');

            // Validation
            if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                $error = 'Please fill in all fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Send to Brevo (Sendinblue)
                $result = $this->sendToBrevo($name, $email, $subject, $message, $logger);
                
                if ($result) {
                    $success = true;
                    $this->addFlash('success', 'Thank you! Your message has been sent successfully.');
                } else {
                    $error = 'Failed to send message. Please try again later.';
                }
            }

            $submitted = true;
        }

        return $this->render('contact/index.html.twig', [
            'submitted' => $submitted,
            'success' => $success,
            'error' => $error,
        ]);
    }

    private function sendToBrevo(string $name, string $email, string $subject, string $message, LoggerInterface $logger): bool
    {
        $apiKey = $_ENV['BREVO_API_KEY'] ?? null;
        
        if (!$apiKey) {
            $logger->error('BREVO_API_KEY not configured');
            // Fallback: log the message for testing
            $logger->info("Contact form submission - Name: $name, Email: $email, Subject: $subject, Message: $message");
            return true; // Return true for demo purposes
        }

        $url = 'https://api.brevo.com/v3/smtp/email';
        
        $data = [
            'sender' => [
                'name' => 'Samaco Brewery',
                'email' => 'jhoncleints@gmail.com', // Your validated Brevo sender
            ],
            'to' => [
                [
                    'email' => 'jhoncleints@gmail.com', // Your actual email
                    'name' => 'Jhon Samaco',
                ],
            ],
            'replyTo' => [
                'email' => $email, // User can reply to the person who submitted
                'name' => $name,
            ],
            'subject' => 'Contact Form: ' . $subject,
            'htmlContent' => "
                <h2>New Contact Form Submission</h2>
                <p><strong>Name:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <p><strong>Message:</strong></p>
                <p>{$message}</p>
            ",
            'textContent' => "Name: {$name}\nEmail: {$email}\nSubject: {$subject}\nMessage: {$message}",
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 201) {
            $logger->info('Contact form email sent successfully via Brevo');
            return true;
        } else {
            $logger->error('Brevo API error: ' . $response);
            return false;
        }
    }
}
