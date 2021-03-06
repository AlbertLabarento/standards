<?php
declare(strict_types=1);

namespace NatePage\Standards\Tools;

class PhpCpd extends WithConfigTool
{
    /**
     * {@inheritdoc}
     *
     * @throws \NatePage\Standards\Exceptions\BinaryNotFoundException
     */
    protected function getCli(): string
    {
        $config = $this->config->dump();

        return \sprintf(
            '%s --ansi --min-lines=%s --min-tokens=%s %s',
            $this->resolveBinary(),
            $config['phpcpd.min_lines'],
            $config['phpcpd.min_tokens'],
            $this->spacePaths($config['paths'])
        );
    }

    /**
     * Get tool identifier.
     *
     * @return string
     */
    public function getId(): string
    {
        return 'phpcpd';
    }

    /**
     * Get tool name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'PHPCPD';
    }
}
