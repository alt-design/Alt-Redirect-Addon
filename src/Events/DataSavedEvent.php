<?php

namespace AltDesign\AltRedirect\Events;

use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;
use AltDesign\AltRedirect\Events\IMessage;

class DataSavedEvent extends Event implements ProvidesCommitMessage
{
    public $item;

    public function __construct(IMessage $item)
    {
        $this->item = $item;
    }

    public function commitMessage()
    {
        return $this->item->getMessage();
    }
}
