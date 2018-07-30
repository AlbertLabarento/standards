<?php
declare(strict_types=1);

namespace NatePage\Standards\Runners;

use NatePage\Standards\Interfaces\ToolsRunnerInterface;
use NatePage\Standards\Traits\ToolsAwareTrait;
use NatePage\Standards\Traits\UsesStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Process\Process;

class ToolsRunner extends WithConsoleRunner implements ToolsRunnerInterface
{
    use ToolsAwareTrait;
    use UsesStyle;

    /**
     * @var \NatePage\Standards\Runners\ProcessRunner[]
     */
    private $runnings = [];

    /**
     * @var bool
     */
    private $successful = true;

    /**
     * Check if currently running.
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return empty($this->runnings) === false;
    }

    /**
     * Check if all tools were successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * {@inheritdoc}
     */
    protected function defineRequiredProperties(): array
    {
        return \array_merge(parent::defineRequiredProperties(), ['tools']);
    }

    /**
     * Do run current instance.
     *
     * @return void
     *
     * @throws \NatePage\Standards\Exceptions\MissingRequiredPropertiesException
     */
    protected function doRun(): void
    {
        $style = $this->style($this->input, $this->output);

        if ($this->tools->isEmpty()) {
            $style->error('No tools to run.');

            return;
        }

        foreach ($this->tools->all() as $tool) {
            $processRunner = $this->getProcessRunner($this->input, $this->getOutputForProcess($style))
                ->setTitle(\sprintf('Running %s', $tool->getName()))
                ->setProcess(new Process($tool->getCli()));

            $this->runnings[] = $processRunner->run();
        }

        while (\count($this->runnings)) {
            /**
             * @var int $index
             * @var \NatePage\Standards\Runners\ProcessRunner $processRunner
             */
            foreach ($this->runnings as $index => $processRunner) {
                // If process still running, skip
                if ($processRunner->isRunning()) {
                    continue;
                }

                // If process not successful, tools runner not successful neither
                if ($processRunner->isSuccessful() === false) {
                    $this->successful = false;
                }

                $processRunner->close();

                unset($this->runnings[$index]);
            }
        }
    }

    /**
     * Get output for process runners.
     *
     * @param StyleInterface $style
     *
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    private function getOutputForProcess(StyleInterface $style): OutputInterface
    {
        if ($this->output instanceof ConsoleOutputInterface) {
            return $this->output->section();
        }

        $style->warning(\sprintf(
            'Current output does not support sections, no guarantee about the result. Please prefer using %s',
            ConsoleOutputInterface::class
        ));

        return $this->output;
    }

    /**
     * Get process runner.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \NatePage\Standards\Runners\ProcessRunner
     */
    private function getProcessRunner(InputInterface $input, OutputInterface $output): ProcessRunner
    {
        $processRunner = new ProcessRunner();

        $processRunner->setInput($input);
        $processRunner->setOutput($output);

        return $processRunner;
    }
}
