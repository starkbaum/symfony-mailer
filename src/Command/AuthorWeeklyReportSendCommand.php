<?php

namespace App\Command;

use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Knp\Snappy\Pdf;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\NamedAddress;
use Twig\Environment;

class AuthorWeeklyReportSendCommand extends Command
{
    protected static $defaultName = 'app:author-weekly-report:send';
    private $userRepository;
    private $articleRepository;
    private $mailer;
    private $twig;
    private $pdf;

    public function __construct(
        UserRepository $userRepository,
        ArticleRepository $articleRepository,
        MailerInterface $mailer,
        Environment $twig,
        Pdf $pdf
    )
    {
        parent::__construct(null);
        $this->userRepository = $userRepository;
        $this->articleRepository = $articleRepository;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->pdf = $pdf;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send weekly reports to authors')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $authors = $this->userRepository->findAllSubscribedToNewsletter();

        $io->progressStart(count($authors));

        foreach ($authors as $author) {
            $io->progressAdvance();
            $articles = $this->articleRepository->findAllPublishedLastWeekByAuthor($author);

            if (count($articles) === 0) {
                continue;
            }

            $html = $this->twig->render('email/author-weekly-report-pdf.html.twig', [
                'articles' => $articles,
            ]);

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

        $io->progressFinish();

        $io->success('Weekly reports were sent to authors');
    }
}
