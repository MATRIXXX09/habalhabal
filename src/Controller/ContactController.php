<?php

namespace App\Controller;

use App\Entity\Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $success = false;
        $error = false;


        if ($request->isMethod('POST')) {
            try {
                $logger->info('Contact form POST received.');
                // Get form data
                $name = $request->request->get('name');
                $email = $request->request->get('email');
                $phone = $request->request->get('phone');
                $subject = $request->request->get('subject');
                $messageText = $request->request->get('message');

                $logger->info('Contact form data extracted.', compact('name', 'email', 'phone', 'subject', 'messageText'));

                // Validate
                if (!$name || !$email || !$subject || !$messageText) {
                    $error = 'Please fill in all required fields.';
                    $logger->warning('Contact form validation failed: missing fields.', compact('name', 'email', 'subject', 'messageText'));
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                    $logger->warning('Contact form validation failed: invalid email.', ['email' => $email]);
                } else {
                    $logger->info('Contact form validation passed. Saving message.');
                    // Create and save message
                    $message = new Message();
                    $message->setSenderEmail($email);
                    $message->setSubject($subject);
                    $message->setMessage("Name: $name\nPhone: $phone\n\n" . $messageText);

                    $em->persist($message);
                    $em->flush();
                    $logger->info('Contact form message saved to DB.', ['email' => $email, 'subject' => $subject]);

                    // Send admin notification email
                    $logger->info('About to send admin notification.');
                    $this->sendAdminNotification($mailer, $logger, $name, $email, $subject, $messageText, $phone);
                    $logger->info('Returned from sendAdminNotification.');

                    $success = true;
                }
            } catch (\Exception $e) {
                $error = 'An error occurred. Please try again later.';
                $logger->error('Contact form exception.', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
        }

        return $this->render('contact/index.html.twig', [
            'success' => $success,
            'error' => $error,
        ]);
    }

    private function sendAdminNotification(MailerInterface $mailer, LoggerInterface $logger, string $name, string $email, string $subject, string $messageText, ?string $phone): void
    {
        try {
            $adminEmail = getenv('ADMIN_EMAIL') ?: 'manyhandy594@gmail.com';
            $fromEmail = getenv('MAILER_FROM') ?: 'manyhandy594@gmail.com';
            
            $emailMessage = (new Email())
                ->from($fromEmail)
                ->replyTo($email)
                ->to($adminEmail)
                ->subject("New Contact Form Submission: $subject")
                ->html("<html>
                    <body style='font-family: Arial, sans-serif; color: #333;'>
                        <div style='background: linear-gradient(135deg, #1a1a1a 0%, #15803d 100%); color: white; padding: 20px; border-radius: 5px 5px 0 0;'>
                            <h2 style='margin: 0; font-size: 24px;'>New Contact Form Submission</h2>
                        </div>
                        <div style='padding: 20px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 0 0 5px 5px;'>
                            <p><strong style='color: #15803d;'>Name:</strong> " . htmlspecialchars($name) . "</p>
                            <p><strong style='color: #15803d;'>Email:</strong> <a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></p>
                            <p><strong style='color: #15803d;'>Phone:</strong> " . htmlspecialchars($phone ?: 'Not provided') . "</p>
                            <p><strong style='color: #15803d;'>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                            <hr style='border: none; border-top: 2px solid #15803d; margin: 20px 0;'>
                            <h3 style='color: #15803d;'>Message:</h3>
                            <p>" . nl2br(htmlspecialchars($messageText)) . "</p>
                        </div>
                    </body>
                </html>");
            
            $mailer->send($emailMessage);

            $logger->info('Contact admin notification sent.', [
                'to' => $adminEmail,
                'from' => $fromEmail,
                'subject' => $subject,
                'reply_to' => $email,
            ]);
        } catch (\Throwable $e) {
            $logger->error('Failed to send admin notification.', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'subject' => $subject,
                'reply_to' => $email,
            ]);
        }
    }
}

