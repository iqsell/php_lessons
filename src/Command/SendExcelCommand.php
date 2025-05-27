<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use App\Repository\DepartmentRepository;
use App\Repository\UserRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

use function PHPSTORM_META\argumentsSet;

#[AsCommand(
    name: 'app:send-excel',
    description: 'Creates a new user.',
    hidden: false,
    aliases: ['app:add-user'],
)]
class SendExcelCommand extends Command
{

    private const RECIPIENT_EMAIL = 'gwelki89@gmail.com';
    private const SENDER_EMAIL = 'gwelki89@gmail.com';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepartmentRepository $departmentRepository,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir
    ) {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $io = new SymfonyStyle($input, $output);

        $recipientEmail = self::RECIPIENT_EMAIL;

        $users = $this->userRepository->findAll();

        if (empty($users)) {
            $io->error('В базе данных нет пользователей для экспорта.');
            return Command::FAILURE;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            'ID', 'Имя', 'Фамилия', 'Возраст', 'Статус',
            'Email', 'Telegram', 'Адрес', 'Отдел', 'Фото'
        ], null, 'A1');

        $row = 2;
        foreach ($users as $user) {
            $sheet->fromArray([
                $user->getId(),
                $user->getFirstName(),
                $user->getLastName(),
                $user->getAge(),
                $user->getStatus(),
                $user->getEmail(),
                $user->getTelegram(),
                $user->getAddress(),
                $user->getDepartment()?->getName() ?? 'N/A',
                $user->getIcon()
            ], null, "A{$row}");
            $row++;
        }

        $fileName = 'users_export_' . date('Y-m-d_His') . '.xlsx';
        $filePath = $this->projectDir . '/var/' . $fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $io->success('Excel файл создан: ' . $filePath);

        $email = (new Email())
            ->from(self::SENDER_EMAIL)
            ->to($recipientEmail)
            ->subject('Экспорт пользователей - ' . date('Y-m-d H:i:s'))
            ->text('Во вложении находится экспорт данных пользователей.');
        // ->attachFromPath($filePath);

        try {
            $this->mailer->send($email);
            $io->success('Отчет отправлен на email: ' . $recipientEmail);

        } catch (\Exception $e) {
            $io->error('Ошибка при отправке email: ' . $e->getMessage());
            $io->note('Файл сохранен локально: ' . $filePath);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}