<?php declare(strict_types=1);

namespace Profitroom\CodingStandards\Command;

use Composer\Command\BaseCommand;
use Profitroom\CodingStandards\PackageConfigReader;
use Profitroom\CodingStandards\RulesetLoader;
use SplFileInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Finder\Finder;

class GitHooksCommand extends BaseCommand
{
    protected const HOOKS_DIR = __DIR__ . '/../../githooks/';

    protected function configure(): void
    {
        $this->setName('cs:git-hooks')
            ->setDescription('Create git hooks')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force operation even if the hook already exists'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');

        foreach ($this->getAvailableHooks() as $hook => $hookFile) {
            if (!$input->getOption('force')) {
                if (!$helper->ask($input, $output, $this->confirmationQuestion($hook))) {
                    continue;
                }
            }

            $this->createHook($hook, $hookFile);
            $output->writeln("<info>Hooks $hook has been created successfully!</info>");
        }
    }

    protected function confirmationQuestion(string $hook): ConfirmationQuestion
    {
        return new ConfirmationQuestion(
            "Hook <info>$hook</info> already exists! Would you like to override ? [<comment>yes/no</comment>] ",
            false,
            '/^(yes|y)/i'
        );
    }

    protected function createHook(string $hook, string $source): void
    {
        copy($source, './.git/hooks/' . $hook);
    }

    protected function getAvailableHooks(): array
    {
        $hooks =[];

        foreach($this->getHookFiles() as $file){
            $hooks[$file->getFilename()] = $file->getPathname();
        }

        return $hooks;
    }

    protected function getConfigurationName(): string
    {
        $packageConfig = new PackageConfigReader($this->getComposer()->getPackage());

        return strtolower($packageConfig->codingStandardsInstance()->name());
    }

    /**
     * @return Finder|SplFileInfo[]
     */
    public function getHookFiles()
    {
        $packageDir = self::HOOKS_DIR.'/'.$this->getConfigurationName();

        $dirs = array_filter([
            self::HOOKS_DIR,
            is_dir($packageDir)? $packageDir: null
        ]);

        return Finder::create()->in($dirs)->files();
    }
}
