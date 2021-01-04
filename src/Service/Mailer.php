<?php


namespace App\Service;


use App\Entity\User;
use Knp\Snappy\Pdf;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\NamedAddress;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Mailer
{
    private $mailer;
    private $twig;
    private $pdf;
    private $entrypointLookup;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        Pdf $pdf,
        EntrypointLookupInterface $entrypointLookup
    )
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->pdf = $pdf;
        $this->entrypointLookup = $entrypointLookup;
    }

    /**
     * @param User $user
     * @throws TransportExceptionInterface
     */
    public function sendWelcomeMessage(User $user)
    {
        $email = (new TemplatedEmail())
            ->from(new NamedAddress('alienmailer@example.com', 'The SpaceBar'))
            ->to(new NamedAddress($user->getEmail(), $user->getFirstName()))
            ->subject('Welcome to the SpaceBar!')
            ->htmlTemplate('email/welcome.html.twig')
            ->context([
                //'user' => $user,
            ]);

        $this->mailer->send($email);
    }

    /**
     * @param User $author
     * @param array $articles
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendAuthorWeeklyReportMessage(User $author, array $articles)
    {
        $html = $this->twig->render('email/author-weekly-report-pdf.html.twig', [
            'articles' => $articles,
        ]);

        $this->entrypointLookup->reset();

        $this->pdf->setOption("enable-local-file-access", true);
        $pdf = $this->pdf->getOutputFromHtml($html);

        $email = (new TemplatedEmail())
            ->from(new NamedAddress('alienmailer@example.com', 'The SpaceBar'))
            ->to(new NamedAddress($author->getEmail(), $author->getFirstName()))
            ->subject('Your weekly report on the SpaceBar')
            ->htmlTemplate('email/author-weekly-report.html.twig')
            ->context([
                'author' => $author,
                'articles' => $articles,
            ])
            ->attach($pdf, sprintf('weekly-report-%s.pdf', date('Y-m-d ')));

        $this->mailer->send($email);
    }
}