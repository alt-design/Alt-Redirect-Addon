<?php

namespace AltDesign\AltRedirect\Events;

interface IMessage
{
    public function getMessage(): string;
}

class DataSavedEventMessage implements IMessage
{
    private string $msg;

    public function __construct(string $message)
    {
        $this->msg = $message;
    }

    public function getMessage(): string
    {
        return $this->msg;
    }
}
