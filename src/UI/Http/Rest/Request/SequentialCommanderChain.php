<?php

declare(strict_types=1);

namespace SharedBundle\UI\Http\Rest\Request;

final readonly class SequentialCommanderChain implements CommanderChainInterface
{
    /** @var CommanderInterface[] */
    private array $commanders;

    public function __construct(
        CommanderInterface ...$commanders,
    ) {
        $this->commanders = $commanders;
    }

    #[\Override]
    public function doWithRequest(InputBag $input): void
    {
        foreach ($this->commanders as $request) {
            if ($request->support($input)) {
                $request->doWithRequest($input);
            }
        }
    }
}
